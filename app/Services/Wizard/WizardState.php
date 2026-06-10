<?php

namespace App\Services\Wizard;

use Illuminate\Contracts\Session\Session;

/*
|--------------------------------------------------------------------------
| WizardState
|--------------------------------------------------------------------------
| Holds the in-progress application entirely in the session — no DB writes,
| matching the one-way submission design. Tracks:
|   - applicant type
|   - per-step captured data
|   - which steps have been completed (gates forward jumps)
|   - the furthest step reached
|
| Uploaded files are NOT stored here; TempFileService (Phase 4) owns those
| and this state only keeps references/metadata once that lands.
*/

class WizardState
{
    private const KEY = 'wizard';

    public function __construct(private readonly Session $session) {}

    /* ----------------------------------------------------------------
     | Lifecycle
     | ---------------------------------------------------------------- */

    /** Start (or restart) a flow for the given applicant type. */
    public function start(string $type): void
    {
        $this->session->put(self::KEY, [
            'type'      => $type,
            'data'      => [],
            'files'     => [],
            'completed' => [],
            'token'     => app(\App\Services\Files\TempFileService::class)->newToken(),
            'started_at' => now()->toIso8601String(),
        ]);
    }

    /** Per-session token that scopes temporary uploads. */
    public function token(): ?string
    {
        return $this->bag()['token'] ?? null;
    }

    public function reset(): void
    {
        $this->session->forget(self::KEY);
    }

    public function exists(): bool
    {
        return $this->session->has(self::KEY) && $this->type() !== null;
    }

    private function bag(): array
    {
        return $this->session->get(self::KEY, []);
    }

    private function put(string $key, mixed $value): void
    {
        $bag = $this->bag();
        $bag[$key] = $value;
        $this->session->put(self::KEY, $bag);
    }

    /* ----------------------------------------------------------------
     | Type
     | ---------------------------------------------------------------- */

    public function type(): ?string
    {
        return $this->bag()['type'] ?? null;
    }

    public function flow(): ?WizardFlow
    {
        $type = $this->type();
        return $type ? WizardFlow::make($type) : null;
    }

    /* ----------------------------------------------------------------
     | Per-step data
     | ---------------------------------------------------------------- */

    /** Merge validated data for a step (preserves untouched keys). */
    public function setStepData(string $stepKey, array $data): void
    {
        $bag = $this->bag();
        $bag['data'][$stepKey] = array_merge($bag['data'][$stepKey] ?? [], $data);
        $this->session->put(self::KEY, $bag);
    }

    public function stepData(string $stepKey): array
    {
        return $this->bag()['data'][$stepKey] ?? [];
    }

    /** All captured data across every step. */
    public function allData(): array
    {
        return $this->bag()['data'] ?? [];
    }

    /** Convenience: a single field from a step. */
    public function get(string $stepKey, string $field, mixed $default = null): mixed
    {
        return $this->stepData($stepKey)[$field] ?? $default;
    }

    /* ----------------------------------------------------------------
     | Uploaded file metadata (keyed by document key, not step)
     | ----------------------------------------------------------------
     | Files themselves live on disk via TempFileService; here we keep only
     | the returned metadata so the review screen and payload mapper can
     | reference documents without touching disk.
     */
    public function setFile(string $documentKey, array $meta): void
    {
        $bag = $this->bag();
        $bag['files'][$documentKey] = $meta;
        $this->session->put(self::KEY, $bag);
    }

    public function file(string $documentKey): ?array
    {
        return $this->bag()['files'][$documentKey] ?? null;
    }

    public function hasFile(string $documentKey): bool
    {
        return isset($this->bag()['files'][$documentKey]);
    }

    /** @return array<string,array<string,mixed>> all stored file metadata */
    public function allFiles(): array
    {
        return $this->bag()['files'] ?? [];
    }

    public function forgetFile(string $documentKey): void
    {
        $bag = $this->bag();
        unset($bag['files'][$documentKey]);
        $this->session->put(self::KEY, $bag);
    }

    /* ----------------------------------------------------------------
     | Completion tracking
     | ---------------------------------------------------------------- */

    public function markCompleted(string $stepKey): void
    {
        $completed = $this->completedSteps();
        if (! in_array($stepKey, $completed, true)) {
            $completed[] = $stepKey;
            $this->put('completed', $completed);
        }
    }

    public function completedSteps(): array
    {
        return $this->bag()['completed'] ?? [];
    }

    public function isCompleted(string $stepKey): bool
    {
        return in_array($stepKey, $this->completedSteps(), true);
    }

    /**
     * Can the user navigate to $stepKey right now?
     * Allowed if: it's already completed, OR it's the first not-yet-completed
     * step (i.e. the natural next step). Prevents skipping ahead.
     */
    public function canAccess(string $stepKey, WizardFlow $flow): bool
    {
        if ($this->isCompleted($stepKey)) {
            return true;
        }

        foreach ($flow->keys() as $key) {
            if ($this->isCompleted($key)) {
                continue;
            }
            // First incomplete step is the only reachable un-completed one.
            return $key === $stepKey;
        }

        return false;
    }

    /** The step the user should resume on (first incomplete, else last). */
    public function resumeKey(WizardFlow $flow): string
    {
        foreach ($flow->keys() as $key) {
            if (! $this->isCompleted($key)) {
                return $key;
            }
        }
        return $flow->lastKey();
    }

    /** True once every non-review step is complete. */
    public function isReadyForReview(WizardFlow $flow): bool
    {
        foreach ($flow->keys() as $key) {
            if ($key === $flow->lastKey()) {
                continue; // the review step itself
            }
            if (! $this->isCompleted($key)) {
                return false;
            }
        }
        return true;
    }
}
