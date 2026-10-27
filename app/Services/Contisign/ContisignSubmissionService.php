<?php

namespace App\Services\Contisign;

use App\Services\Wizard\WizardState;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/*
|--------------------------------------------------------------------------
| ContisignSubmissionService
|--------------------------------------------------------------------------
| Orchestrates the full Contisign submission. Per the integration, each
| application is submitted against BOTH templates (solicitud + buro). For
| each template the sequence is:
|
|   1. Upload annex files  → annexed[] entries   (AnnexUploader)
|   2. createUniKey        → unikey / contractId / personId
|   3. createDataTemplate  → document id          (html + DataTemplates + annexed)
|   4. sendSigns           → signatures bound to the document id
|
| The annex list is OUR own list (not the template's Annexed array): we send
| whatever documents the wizard collected, labeled for the reviewer.
*/

class ContisignSubmissionService
{
    public function __construct(
        private readonly ContisignAuthService $auth,
        private readonly ContisignTemplateMapper $mapper,
        private readonly ContisignAnnexUploader $annexUploader,
        private readonly WizardState $state,
    ) {}

    /** Our document-key → annex label map (Spanish, for the reviewer). */
    private const ANNEX_LABELS = [
        'id_front'                  => 'Identificación (frente)',
        'id_back'                   => 'Identificación (reverso)',
        'proof_of_address'          => 'Comprobante de Domicilio',
        'tax_certificate'           => 'Constancia de Situación Fiscal',
        'articles_of_incorporation' => 'Acta Constitutiva',
        'power_of_attorney'         => 'Acta de Poderes',
        'annual_return_1'      => 'Declaración Anual (última)',
        'annual_return_2'      => 'Declaración Anual (anterior)',
        'financials_partial_1' => 'Estados Financieros Parciales (página 1)',
        'financials_partial_2' => 'Estados Financieros Parciales (página 2)',
        'credit_over_threshold'         => [
            'annual_return_1'      => 'Declaración Anual (última)',
            'annual_return_2'      => 'Declaración Anual (anterior)',
            'financials_partial_1' => 'Estados Financieros Parciales (página 1)',
            'financials_partial_2' => 'Estados Financieros Parciales (página 2)',
        ],
    ];

    /**
     * Submit the full application (both templates).
     *
     * @param array $payload normalized PayloadMapper output
     * @return array{ok:bool, documents:array<string,string>, error?:string}
     */
    public function submit(array $payload): array
    {
        $documents = [];

        try {
            foreach (['solicitud', 'buro'] as $templateKey) {
                $docId = $this->submitTemplate($templateKey, $payload);
                $documents[$templateKey] = $docId;
            }

            return ['ok' => true, 'documents' => $documents];
        } catch (\Throwable $e) {
            Log::error('Contisign submission failed.', [
                'error'     => $e->getMessage(),
                'completed' => $documents,
            ]);
            return ['ok' => false, 'documents' => $documents, 'error' => $e->getMessage()];
        }
    }

    /* ----------------------------------------------------------------
     | Per-template sequence
     | ---------------------------------------------------------------- */

    private function submitTemplate(string $templateKey, array $payload): string
    {
        $cfg      = $this->resolveTemplateConfig($templateKey, $payload);
        $mapped   = $this->mapper->map($templateKey, $cfg, $payload);
        $template = $mapped['template'];

        $annexed = $templateKey === 'solicitud' ? $this->buildAnnexed($payload) : [];

        // --- Line-director debug (toggle with CONTISIGN_DEBUG_SIGNATURES=true) ---
        // Logs what the RUNNING app sees: the service chosen, the resolved
        // director, the config keys actually loaded, and the final signer list.
        // Lets us tell a stale config cache / missing key apart from a null
        // service. Remove or leave the flag off in normal operation.
        if ($templateKey === 'solicitud' && config('contisign.debug_signatures', false)) {
            $service = $payload['service'] ?? null;
            $map     = config('contisign.service_line_directors', []);
            Log::info('[LINE-DIRECTOR DEBUG] solicitud submission', [
                'credit_range'       => $payload['credit_range'] ?? null,
                'service_in_payload' => $service,
                'service_found'      => $service !== null && array_key_exists($service, $map),
                'service_map_keys'   => array_keys($map),
                'resolved_director'  => $this->serviceDirector($service),
                'template_id'        => $template['id'] ?? null,
                'template_name'      => $template['TemplateName'] ?? null,
                'signatures'         => array_map(fn($s) => [
                    'order' => $s['Order'] ?? null,
                    'name'  => $s['Name'] ?? null,
                    'email' => $s['Email'] ?? null,
                    'phone' => $s['PhoneNumber'] ?? null,
                ], $this->buildSignatures('solicitud', $template, $payload)),
            ]);
        }

        // 2. createUniKey
        $uniKey = $this->createUniKey($template);
        // Log::debug("Contisign UniKey created for template $templateKey", ['unikey' => $uniKey]);
        // 3. createDataTemplate → document id
        $documentId = $this->createDataTemplate($templateKey, $payload, $mapped, $uniKey, $annexed);
        // 4. sendSigns
        $this->sendSigns($templateKey, $template, $payload, $documentId);

        // $documentId = 'mocked-document-id';
        return $documentId;
    }

    /**
     * Resolve the template config for a submission. The SOLICITUD varies by the
     * applicant's credit-line range (3 templates); the BURÓ is fixed.
     */
    private function resolveTemplateConfig(string $templateKey, array $payload): array
    {
        if ($templateKey === 'solicitud') {
            $default = config('contisign.solicitud_default_tier', 'under_150k');
            $tier    = $payload['credit_range'] ?? $default;
            return config("contisign.solicitud_templates.$tier")
                ?? config("contisign.solicitud_templates.$default");
        }

        return config("contisign.templates.$templateKey");
    }

    /** Upload every collected document; flatten into the annexed[] array. */
    private function buildAnnexed(array $payload): array
    {
        $annexed = [];
        foreach (($payload['documents'] ?? []) as $key => $docMeta) {
            $label = self::ANNEX_LABELS[$key] ?? Str::headline($key);
            // Normalize single-file metadata (from store()) into parts shape.
            if (! isset($docMeta['parts'])) {
                $docMeta = ['parts' => [[
                    'stored_path' => $docMeta['stored_path'] ?? null,
                    'mime'        => $docMeta['mime'] ?? null,
                    'size'        => $docMeta['size'] ?? 0,
                    'name'        => $docMeta['original_name'] ?? $key,
                ]]];
            }
            // dd($docMeta);
            $entries = $this->annexUploader->uploadDocument($docMeta, $label);
            // $entries = [];
            foreach ($entries as $e) {
                $annexed[] = $e;
            }
        }
        return $annexed;
    }

    private function createUniKey(array $template): array
    {
        $payload = [
            'UniKey'         => $this->generateUniKey($template['TemplateName'] ?? 'DOC'),
            'thisTemplateId' => $template['id'] ?? null,
            'user_id'        => config('contisign.user_id'),
            'version'        => false,
        ];

        $data = $this->post('create_unikey', $payload);

        return [
            'unikey'     => $data['UniKey']     ?? $payload['UniKey'],
            'contractId' => $data['contractId'] ?? ($data['contract_id'] ?? null),
            'personId'   => $data['personId']   ?? ($data['dtperson_id'] ?? null),
        ];
    }

    private function createDataTemplate(
        string $templateKey,
        array $payload,
        array $mapped,
        array $uniKey,
        array $annexed
    ): string {
        $template = $mapped['template'];

        $applicantName = $this->applicantName($payload);

        $body = [
            'Active'          => $template['Active'] ?? true,
            'annexed'         => $annexed,
            'Signsstatus'     => "Este documento no ha sido firmado",
            'contract_id'     => $uniKey['contractId'],
            'DataTemplates'   => $mapped['dataTemplates'],
            'DocumentName'    => ($template['TemplateName'] ?? 'Documento') . ' - ' . $applicantName,
            'dtperson_id'     => $uniKey['personId'],
            'Tags'            => [],
            'Emptytemplate'   => null,
            'user_id'         => config('contisign.user_id'),
            'positionRequired' => $template['positionRequired'] ?? true,
            'UniKey'          => $uniKey['unikey'],
            // 'html'            => "",
            'html'            => $mapped['html'],
            'templateId'      => $template['id'] ?? null,
            'documentUrl'     => null,
            'ConstancePSC'    => $template['PSCreq'] ?? false,
            'OnlyNOM151'      => $template['OnlyNOM151'] ?? false,
            'Fields'          => null,
            'requireSMS'      => false,
            // 'requireSMS'      => $template['requireSMS'] ?? false,
            'signatures'      => $this->buildSignatures($templateKey, $template, $payload),
        ];

        Log::debug('Contisign datatemplate payload', [
            'template' => $templateKey,
            'body' => $body,
            "applicantName" => $applicantName,
        ]);

        $data = $this->post('datatemplate', $body);

        Log::debug("Contisign datatemplate response for $templateKey", ['response' => $data]);

        $documentId = $data['id'] ?? null;
        if (! $documentId) {
            throw new RuntimeException("Contisign datatemplate did not return a document id ($templateKey).");
        }
        return $documentId;
    }

    private function sendSigns(string $templateKey, array $template, array $payload, string $documentId): void
    {
        $signatures = $this->buildSignatures($templateKey, $template, $payload);
        $signatures = array_map(function ($sig) use ($documentId) {
            $sig['documentid'] = $documentId;
            return $sig;
        }, $signatures);
        Log::debug("Contisign Signatures created for template $templateKey", ['signatures' => $signatures]);

        $this->post('send_signs', [
            'signs'  => $signatures,
            'firmas' => count($signatures),
        ]);
    }

    /* ----------------------------------------------------------------
     | Signatures
     | ----------------------------------------------------------------
     | Buró      → one signer, the client (contact / representative).
     | Solicitud → the template's full internal approver chain (sent verbatim
     |             with baked-in positions) plus the service line director in
     |             the single empty-email "DIRECTOR DE LINEA" slot.
     */
    private function buildSignatures(string $templateKey, array $template, array $payload): array
    {
        return $templateKey === 'buro'
            ? $this->buroSignatures($template, $payload)
            : $this->solicitudSignatures($template, $payload);
    }

    /**
     * BURÓ — unchanged: a single signer (the client / representative) mapped
     * onto the template's first UserSigns slot, one entry per SignType.
     */
    private function buroSignatures(array $template, array $payload): array
    {
        $cfg    = config('contisign.templates.buro');
        $signer = $cfg['signer'] ?? 'client';

        if ($signer === 'client') {
            $contact = ($payload['applicant_type'] ?? null) === 'company'
                ? ($payload['representative'] ?? [])
                : ($payload['contact'] ?? []);
            $name  = trim(($contact['first_name'] ?? '') . ' ' . ($contact['last_name'] ?? ''));
            $email = $contact['email'] ?? '';
            $phone = $contact['phone'] ?? '';
        } else {
            $name  = $signer['name'] ?? '';
            $email = $signer['email'] ?? '';
            $phone = $signer['phone'] ?? '';
        }

        $userSign  = $template['UserSigns'][0] ?? [];
        $limitDate = now()->addDays(30)->toIso8601String();

        $signature = $userSign;
        $signature['Name']        = $name;
        $signature['Email']       = $email;
        $signature['LimitDate']   = $limitDate;
        $signature['PhoneNumber'] = $phone;
        $signature['phone']       = (string) $phone;
        if (isset($signature['AditionalInformation']) && is_array($signature['AditionalInformation'])) {
            $signature['AditionalInformation']['name'] = $name;
        }

        $signatures = [];
        foreach (($template['SignType'] ?? ['Firma sencilla']) as $type) {
            $s = $signature;
            $s['Type'] = $type;
            $signatures[] = $s;
        }
        return $signatures;
    }

    /**
     * SOLICITUD — multi-signer. Each tier template already carries its internal
     * approver chain (names, emails, positions, order) plus ONE empty-email
     * "DIRECTOR DE LINEA" slot. We send every UserSigns entry verbatim (keeping
     * its position/order) and fill only that empty slot with the service line
     * director. One signature per signer (templates set numFirmas=1), Type = the
     * document's first allowed SignType.
     *
     * NOTE: with no vendor reference payload, one-signature-per-signer and the
     * first-SignType choice are the best-supported reading of the template
     * (numFirmas=1, a single position per entry). Verify against a live
     * Contisign test before go-live — both assumptions are isolated here.
     */
    private function solicitudSignatures(array $template, array $payload): array
    {
        $director  = $this->serviceDirector($payload['service'] ?? null);
        $type      = $template['SignType'][0] ?? 'Firma sencilla';
        $limitDate = now()->addDays(30)->toIso8601String();

        $signatures = [];
        foreach (($template['UserSigns'] ?? []) as $userSign) {
            $sig = $userSign;

            // The dynamic slot is the one UserSigns entry with no email.
            if (trim((string) ($userSign['Email'] ?? '')) === '') {
                $sig['Name']        = $director['name'] ?? ($userSign['Name'] ?? '');
                $sig['Email']       = $director['email'] ?? '';
                $sig['PhoneNumber'] = (string) ($director['phone'] ?? '');
                $sig['phone']       = (string) ($director['phone'] ?? '');
                if (isset($sig['AditionalInformation']) && is_array($sig['AditionalInformation'])) {
                    $sig['AditionalInformation']['name'] = $sig['Name'];
                }
            } else {
                $sig['phone'] = (string) ($userSign['PhoneNumber'] ?? '');
            }

            $sig['LimitDate'] = $limitDate;
            $sig['Status']    = $userSign['Status'] ?? 'Pendiente';
            $sig['Type']      = $type;
            $signatures[] = $sig;
        }
        return $signatures;
    }

    /** Resolve the service → line-director signer, with a safe fallback. */
    private function serviceDirector(?string $service): array
    {
        $map = config('contisign.service_line_directors', []);
        return $map[$service]
            ?? config('contisign.service_line_director_default', ['name' => '', 'email' => '', 'phone' => '']);
    }

    /* ----------------------------------------------------------------
     | Helpers
     | ---------------------------------------------------------------- */

    private function applicantName(array $payload): string
    {
        $profile = $payload['profile'] ?? [];
        if (($payload['applicant_type'] ?? null) === 'company') {
            return $profile['legal_name'] ?? 'Solicitante';
        }
        return trim(($profile['first_name'] ?? '') . ' ' . ($profile['last_name'] ?? '')) ?: 'Solicitante';
    }

    /** Generate a UniKey from the template name (per provided unikey.php). */
    private function generateUniKey(string $templateName): string
    {
        $templateName = str_replace('-', '', trim($templateName));
        preg_match_all('/[A-Z_]/', $templateName, $matches);
        $letters = implode('', $matches[0]);
        // Append a short random suffix to avoid collisions across submissions.
        return 'OU' . $letters . strtoupper(Str::random(4));
    }

    /** POST a JSON body to a configured endpoint with the auth token. */
    private function post(string $endpointKey, array $body): array
    {
        $url = rtrim((string) config('contisign.base_url'), '/')
            . config("contisign.endpoints.$endpointKey");

        $response = Http::timeout((int) config('contisign.http.timeout', 60))
            ->withHeaders(['Authorization' => $this->auth->token()])
            ->acceptJson()
            ->post($url, $body);

        Log::debug("Contisign request $endpointKey", ['response' => $response->json()]);

        // One retry on auth failure with a forced re-auth.
        if ($response->status() === 401) {
            $this->auth->forget();
            $response = Http::timeout((int) config('contisign.http.timeout', 60))
                ->withHeaders(['Authorization' => $this->auth->token()])
                ->acceptJson()
                ->post($url, $body);
        }

        if (! $response->successful()) {
            Log::error("Contisign $endpointKey failed.", ['status' => $response->status(), 'body' => $response->body()]);
            throw new RuntimeException("Contisign request failed: $endpointKey");
        }

        return $response->json() ?? [];
    }
}
