<?php

namespace App\Http\Controllers;

use App\Http\Requests\Wizard\StepRequestResolver;
use App\Services\Files\TempFileService;
use App\Services\Wizard\WizardFlow;
use App\Services\Wizard\WizardState;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;

/*
|--------------------------------------------------------------------------
| WizardController
|--------------------------------------------------------------------------
| Drives the multi-step onboarding wizard for both applicant types. All
| step ordering comes from config/wizard.php via WizardFlow; all partial
| data lives in the session via WizardState. No DB writes here.
|
| Phase 1 scope: flow selection, step rendering, next/back/jump navigation,
| access guards, resume. Per-step validation is delegated to a seam
| (validateStep) that Phase 2 fills with Form Request classes.
*/

class WizardController extends Controller
{
    public function __construct(
        private readonly WizardState $state,
        private readonly TempFileService $files,
    ) {}

    /* ----------------------------------------------------------------
     | Entry: applicant-type selector
     | ---------------------------------------------------------------- */

    public function start(): View
    {
        return view('wizard.start');
    }

    /** Handle the type selection and enter the flow. */
    public function begin(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'applicant_type' => ['required', 'string', function ($attr, $value, $fail) {
                if (! WizardFlow::isValidType($value)) {
                    $fail(__('Please choose a valid applicant type.'));
                }
            }],
        ]);

        $this->state->start($validated['applicant_type']);
        $flow = WizardFlow::make($validated['applicant_type']);

        return redirect()->route('wizard.step', ['step' => $flow->firstKey()]);
    }

    /* ----------------------------------------------------------------
     | Display a step
     | ---------------------------------------------------------------- */

    public function show(string $step): View|RedirectResponse
    {
        if (! $this->state->exists()) {
            return redirect()->route('wizard.start');
        }

        $flow = $this->state->flow();

        if (! $flow->hasStep($step)) {
            return redirect()->route('wizard.step', ['step' => $this->state->resumeKey($flow)]);
        }

        // Guard: can't jump ahead past steps that haven't been completed.
        if (! $this->state->canAccess($step, $flow)) {
            return redirect()
                ->route('wizard.step', ['step' => $this->state->resumeKey($flow)])
                ->with('warning', __('Please complete the previous steps first.'));
        }

        $definition = $flow->stepByKey($step);

        // On the review step, hand the view a normalized payload so it renders
        // a complete summary without branching on applicant type. Guard: only
        // build it once all content steps are complete.
        $payload = null;
        if ($flow->isLast($step)) {
            if (! $this->state->isReadyForReview($flow)) {
                return redirect()
                    ->route('wizard.step', ['step' => $this->state->resumeKey($flow)])
                    ->with('warning', __('Please complete all steps before reviewing.'));
            }
            $payload = app(\App\Services\Payload\PayloadMapper::class)->build();
        }

        return view('wizard.shell', [
            'flow'        => $flow,
            'step'        => $step,
            'position'    => $flow->positionOf($step),
            'definition'  => $definition,
            'partial'     => "wizard.steps.{$definition['view']}",
            'data'        => $this->state->stepData($step),
            'files'       => $this->state->allFiles(),
            'payload'     => $payload,
            'isFirst'     => $flow->isFirst($step),
            'isLast'      => $flow->isLast($step),
            'stepLabels'  => $flow->labels(),
        ]);
    }

    /* ----------------------------------------------------------------
     | Advance to the next step
     | ---------------------------------------------------------------- */

    public function next(Request $request, string $step): RedirectResponse
    {
        if (! $this->state->exists()) {
            return redirect()->route('wizard.start');
        }

        $flow = $this->state->flow();

        if (! $flow->hasStep($step) || ! $this->state->canAccess($step, $flow)) {
            return redirect()->route('wizard.step', ['step' => $this->state->resumeKey($flow)]);
        }

        // Tell validation which document keys already have a stored file, so a
        // user returning to a document step isn't forced to re-select files.
        $this->injectExistingFileFlags($request, $flow, $step);

        // Validate (per-step Form Request via the resolver).
        $data = $this->validateStep($request, $flow, $step);

        // Persist any newly uploaded files to temp storage and replace the raw
        // UploadedFile instances in $data with serializable metadata (an
        // UploadedFile cannot be stored in the session).
        $data = $this->persistStepFiles($request, $data);

        $this->state->setStepData($step, $data);
        $this->state->markCompleted($step);

        $nextKey = $flow->nextKey($step);

        // Last content step → land on review (review is the final step key).
        if ($nextKey === null) {
            return redirect()->route('wizard.step', ['step' => $flow->lastKey()]);
        }

        return redirect()->route('wizard.step', ['step' => $nextKey]);
    }

    /**
     * Set "{key}_exists" = true on the request for any document key whose file
     * is already stored, so requiredIf rules pass without a re-upload.
     */
    private function injectExistingFileFlags(Request $request, WizardFlow $flow, string $step): void
    {
        $token = $this->state->token();
        if (! $token) {
            return;
        }

        foreach ($this->documentKeysForStep($flow->type, $step) as $key) {
            if ($this->state->hasFile($key) || $this->files->exists($token, $key)) {
                $request->merge(["{$key}_exists" => true]);
            }
        }
    }

    /**
     * Move uploaded files out of validated data into temp storage, recording
     * metadata in state. Returns $data with file fields replaced by their
     * stored metadata (or the previously stored metadata if unchanged).
     */
    private function persistStepFiles(Request $request, array $data): array
    {
        $token = $this->state->token();

        // If identification switched away from INE, drop any stored back image
        // so it doesn't linger into the review screen or payload.
        if (($data['id_type'] ?? null) && $data['id_type'] !== 'ine') {
            if ($this->state->hasFile('id_back')) {
                $this->files->deleteKey($token, 'id_back');
                $this->state->forgetFile('id_back');
            }
            unset($data['id_back']);
        }

        // Conditional credit docs: if the gate is off, drop any previously
        // stored files for those keys so they aren't submitted as annexes.
        // IMPORTANT: only act when THIS submitted step is the one that hosts the
        // credit checkbox. We detect that via a hidden marker field
        // (credit_docs_step=1) that the identification/documents blade always
        // sends. On later steps the marker is absent, so files uploaded earlier
        // are never wrongly deleted. When present but the box is unchecked
        // (boolean false), we purge.
        if ($request->boolean('credit_docs_step') && ! $request->boolean('credit_over_threshold')) {
            foreach (array_keys(config('documents.credit_over_threshold', [])) as $key) {
                if ($this->state->hasFile($key)) {
                    $this->files->deleteKey($token, $key);
                    $this->state->forgetFile($key);
                }
                unset($data[$key]);
            }
        }

        foreach ($data as $field => $value) {
            // Drop the helper flags from persisted data.
            if (str_ends_with($field, '_exists')) {
                unset($data[$field]);
                continue;
            }

            // File fields arrive as arrays of one or more parts (the upload
            // input uses name="key[]"). Detect by first element being a file.
            if (is_array($value) && isset($value[0]) && $value[0] instanceof UploadedFile) {
                $meta = $this->files->storeParts($token, $field, $value);
                $this->state->setFile($field, $meta);
                $data[$field] = $meta;
            } elseif ($value instanceof UploadedFile) {
                // Defensive: a non-array single file.
                $meta = $this->files->storeParts($token, $field, [$value]);
                $this->state->setFile($field, $meta);
                $data[$field] = $meta;
            } elseif (($value === null || $value === []) && $this->state->hasFile($field)) {
                // No new upload but a file already exists → keep prior metadata.
                $data[$field] = $this->state->file($field);
            }
        }

        return $data;
    }

    private function documentKeysForStep(string $type, string $step): array
    {
        $credit = array_keys(config('documents.credit_over_threshold', []));

        return match ("$type.$step") {
            'individual.identification' => array_merge(
                ['id_front', 'id_back'],
                array_keys(config('documents.required.individual', [])),
                $credit
            ),
            'company.documents' => array_merge(
                array_keys(config('documents.required.company', [])),
                $credit
            ),
            'company.representative' => ['id_front', 'id_back'],
            default => [],
        };
    }

    /* ----------------------------------------------------------------
     | Go back (no validation; data already saved)
     | ---------------------------------------------------------------- */

    public function back(string $step): RedirectResponse
    {
        if (! $this->state->exists()) {
            return redirect()->route('wizard.start');
        }

        $flow = $this->state->flow();
        $prev = $flow->previousKey($step);

        return redirect()->route('wizard.step', [
            'step' => $prev ?? $flow->firstKey(),
        ]);
    }

    /* ----------------------------------------------------------------
     | Abandon / restart
     | ---------------------------------------------------------------- */

    public function cancel(): RedirectResponse
    {
        if ($token = $this->state->token()) {
            $this->files->purge($token);
        }
        $this->state->reset();
        return redirect()->route('wizard.start')
            ->with('info', __('Your application was cleared.'));
    }

    /* ----------------------------------------------------------------
     | Final submission
     | ----------------------------------------------------------------
     | Validates the explicit confirmation + full readiness, builds the
     | normalized payload, and (Phase 6) submits to Contisign. For now the
     | Contisign call is a seam; on success we show the success screen and
     | purge temp files.
     */
    public function submit(Request $request): View|RedirectResponse
    {
        if (! $this->state->exists()) {
            return redirect()->route('wizard.start');
        }

        $flow = $this->state->flow();

        // Re-validate full readiness server-side (don't trust the client).
        if (! $this->state->isReadyForReview($flow)) {
            return redirect()
                ->route('wizard.step', ['step' => $this->state->resumeKey($flow)])
                ->with('warning', __('Please complete all steps before submitting.'));
        }

        // Explicit confirmation is required.
        $request->validate([
            'confirm_submission' => ['accepted'],
        ], [
            'confirm_submission.accepted' => __('You must confirm the information before submitting.'),
        ]);

        $payload = app(\App\Services\Payload\PayloadMapper::class)->build();

        // Submit to Contisign (both templates: solicitud + buro).
        $result = app(\App\Services\Contisign\ContisignSubmissionService::class)->submit($payload);

        if (! ($result['ok'] ?? false)) {
            return view('wizard.error', [
                'message' => __('We could not submit your application. Please try again.'),
            ]);
        }

        // Success: purge temp files and clear the session (one-way flow).
        if ($token = $this->state->token()) {
            $this->files->purge($token);
        }
        $applicantType = $payload['applicant_type'];
        $this->state->reset();

        return view('wizard.success', [
            'applicantType' => $applicantType,
        ]);
    }

    /* ----------------------------------------------------------------
     | Validation seam
     | ----------------------------------------------------------------
     | Resolve a per-step FormRequest by "{type}.{stepKey}". Steps with no
     | input (review) resolve to null and are accepted as-is.
     |
     | The FormRequest is built FROM the current request via createFrom so it
     | inherits any flags merged earlier (e.g. "{key}_exists"). Validation
     | failure throws ValidationException, which Laravel redirects back with
     | errors + old input automatically.
     */
    private function validateStep(Request $request, WizardFlow $flow, string $step): array
    {
        $requestClass = StepRequestResolver::resolve($flow->type, $step);

        if ($requestClass === null) {
            return $request->except(['_token', '_method', 'direction']);
        }

        /** @var \App\Http\Requests\Wizard\StepRequest $formRequest */
        $formRequest = $requestClass::createFrom($request);
        $formRequest->setContainer(app())->setRedirector(app('redirect'));
        $formRequest->validateResolved();

        // Pull validated scalars; uploaded files are read back from the
        // original request (validated() returns them, but we re-fetch to be
        // explicit) and handled by persistStepFiles.
        $validated = $formRequest->validated();

        // Re-attach uploaded files from the ORIGINAL request. For array inputs
        // (name="key[]"), $request->file() returns an array of UploadedFile —
        // we must preserve the whole array so all split parts survive. Using
        // the FormRequest's allFiles() here is unreliable for array fields, so
        // we read each document key directly off the request.
        foreach (array_keys($this->fileFieldsFor($flow, $step)) as $field) {
            if (! $request->hasFile($field)) {
                continue;
            }
            $file = $request->file($field);
            // Normalize to an array of parts regardless of single/multiple.
            $validated[$field] = is_array($file) ? array_values($file) : [$file];
        }
        // if ($step === 'documents' || $step === 'identification') {
        //     $f = $request->file('articles_of_incorporation') ?? $request->file('proof_of_address');
        //     dd([
        //         'step' => $step,
        //         'hasFile_articles' => $request->hasFile('articles_of_incorporation'),
        //         'raw_count' => is_array($f) ? count($f) : gettype($f),
        //         'all_files' => array_map(
        //             fn($v) => is_array($v) ? count($v) . ' files' : '1 file',
        //             $request->allFiles()
        //         ),
        //     ]);
        // }
        return $validated;
    }

    /** File field keys (with no value) expected on a given step. */
    private function fileFieldsFor(WizardFlow $flow, string $step): array
    {
        return array_fill_keys($this->documentKeysForStep($flow->type, $step), true);
    }
}
