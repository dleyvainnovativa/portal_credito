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
        $mapped   = $this->mapper->map($templateKey, $payload);
        $template = $mapped['template'];
        $cfg      = config("contisign.templates.$templateKey");
        // dd($templateKey, $payload, $mapped, $cfg);
        if ($templateKey == "solicitud") {
            $annexed = $this->buildAnnexed($payload);
        } else {
            $annexed = [];
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
            'requireSMS'      => $template['requireSMS'] ?? false,
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
     | Buró  → signed by the client (contact name/email/phone).
     | Solicitud → signed by the hardcoded signer from config.
     */
    private function buildSignatures(string $templateKey, array $template, array $payload): array
    {
        $cfg = config("contisign.templates.$templateKey");
        $signer = $cfg['signer'];

        if ($signer === 'client') {
            if ($payload["applicant_type"] == "company") {
                $contact = $payload['representative'] ?? [];
                $name  = trim(($contact['first_name'] ?? '') . ' ' . ($contact['last_name'] ?? ''));
                $email = $contact['email'] ?? '';
                $phone = $contact['phone'] ?? '';
            } else {
                $contact = $payload['contact'] ?? [];
                $name  = trim(($contact['first_name'] ?? '') . ' ' . ($contact['last_name'] ?? ''));
                $email = $contact['email'] ?? '';
                $phone = $contact['phone'] ?? '';
            }
        } else {
            $name  = $signer['name'] ?? '';
            $email = $signer['email'] ?? '';
            $phone = $signer['phone'] ?? '';
        }

        $userSign = $template['UserSigns'][0] ?? [];
        $limitDate = now()->addDays(30)->toIso8601String();

        $signature = [
            'Name'         => $name,
            'Email'        => $email,
            'Charge'       => $userSign['Charge'] ?? 'Signed',
            'Position'     => $userSign['Position'] ?? '',
            'BusinessName' => $userSign['BusinessName'] ?? '',
            'Order'        => $userSign['Order'] ?? 1,
            'external'     => true,
            'bgColor'      => $userSign['bgColor'] ?? '3dc108',
            'x'            => $userSign['x'] ?? null,
            'y'            => $userSign['y'] ?? null,
            'width'        => $userSign['width'] ?? null,
            'height'       => $userSign['height'] ?? null,
            'dimension'    => $userSign['dimension'] ?? ['w' => 612, 'h' => 792],
            'page'         => $userSign['page'] ?? 0,
            'Status'       => 'Pendiente',
            'editing'      => false,
            'LimitDate'    => $limitDate,
            'PhoneNumber'  => (string) $phone,
            'phone'        => (string) $phone,
            'AditionalInformation' => [
                'x'          => $userSign['x'] ?? null,
                'y'          => $userSign['y'] ?? null,
                'width'      => $userSign['width'] ?? null,
                'height'     => $userSign['height'] ?? null,
                'bgcolor'    => '#' . ($userSign['bgColor'] ?? '3dc108') . '99',
                'name'       => $name,
                'page'       => $userSign['page'] ?? 0,
                'dimensions' => $userSign['dimension'] ?? ['w' => 612, 'h' => 792],
            ],
        ];

        // One signature per SignType (matches the provided send_signatures.php).
        $signatures = [];
        $types = $template['SignType'] ?? ['Firma sencilla'];
        foreach ($types as $type) {
            $s = $signature;
            $s['Type'] = $type;
            $signatures[] = $s;
        }
        return $signatures;
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
