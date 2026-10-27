<?php

/*
|--------------------------------------------------------------------------
| Contisign integration configuration
|--------------------------------------------------------------------------
| Auth + submission settings for the electronic-signature provider.
| Used by ContisignAuthService, ContisignClient, AnnexUploader, and the
| submission service (Phase 6).
*/

return [

    // Auth and API share the same base URL (dev = api.contisign...).
    'base_url' => env('CONTISIGN_BASE_URL', 'https://api.contisign.com.mx'),
    // 'base_url' => env('CONTISIGN_BASE_URL', 'https://apidev.contisign.com.mx'),

    'credentials' => [
        'email'    => env('CONTISIGN_EMAIL'),
        'password' => env('CONTISIGN_PASSWORD'),
    ],

    // The Contisign user id sent in createUniKey / datatemplate payloads.
    'user_id' => env('CONTISIGN_ID'),

    /*
    | Document HTML behavior (for diagnosing document generation):
    |   send_filled_html = false → send html:"" and let Contisign render the
    |     PDF server-side from DataTemplates + the stored template (known-good).
    |   send_filled_html = true  → send the template HTML with <s>var</s>
    |     placeholders filled in.
    | strip_accents = true → transliterate accented chars (Río → Rio) in the
    |   filled HTML, in case the generator can't handle them.
    */
    'send_filled_html' => env('CONTISIGN_SEND_FILLED_HTML', false),
    'strip_accents'    => env('CONTISIGN_STRIP_ACCENTS', false),

    // When true, logs the resolved line-director + full signer list on each
    // solicitud submission (see ContisignSubmissionService::submitTemplate).
    // For diagnosing signer wiring in prod; leave false in normal operation.
    'debug_signatures' => env('CONTISIGN_DEBUG_SIGNATURES', false),

    'endpoints' => [
        'signin'        => '/api/auth/signin',
        'refresh'       => '/api/auth/refreshToken',
        'upload_file'   => '/api/uploaddocumentfile',
        'create_unikey' => '/api/createUniKey',
        'datatemplate'  => '/api/datatemplate',
        'send_signs'    => '/api/signs',
    ],

    'token' => [
        'cache_key'    => 'contisign.token',
        // Fallback lifetime (seconds) when the API omits expiresIn.
        'fallback_ttl' => 3600,
        // Refresh when the token is within this many seconds of expiring.
        'refresh_skew' => 120,
    ],

    'http' => [
        'timeout'         => env('CONTISIGN_TIMEOUT', 60),
        'connect_timeout' => env('CONTISIGN_CONNECT_TIMEOUT', 15),
        'retries'         => env('CONTISIGN_RETRIES', 2),
    ],

    // Annex (anexo) upload constraints. Contisign accepts image/PDF up to
    // 15 MB per annexed file; larger PDFs are split (frontend) into parts,
    // each of which must satisfy this limit. Backend re-checks as a guard.
    'annex' => [
        'max_size_kb' => env('CONTISIGN_ANNEX_MAX_KB', 15360), // 15 MB
        'mimes'       => ['pdf', 'jpg', 'jpeg', 'png'],
    ],

    /*
    | The two templates every application is submitted against. IDs come from
    | the template JSON files. The submission runs once per template.
    |
    | 'signer' resolves who signs:
    |   - 'client'  → uses the wizard contact (name/email/phone)
    |   - a fixed array → hardcoded signer
    */
    'templates' => [

        // Buró is unchanged: one template, signed by the client (contact /
        // representative). Signers for the SOLICITUD come from the template
        // itself now (see 'solicitud_templates' below), not from here.
        'buro' => [
            'id'        => env('CONTISIGN_TPL_BURO_ID', '61ee9d8a-1acb-4349-b190-fd125995d676'),
            'name'      => '00.BURO DE CREDITO (con Correo institucional)',
            'json_path' => 'contisign/templates/buro_credito.json',
            'signer'    => 'client', // client name/email/phone from the contact step
        ],
    ],

    /*
    | SOLICITUD DE CRÉDITO — one template per credit-line range. The applicant
    | picks a range in the intake sheet (credit_range); the chosen template
    | carries its own internal approver chain WITH positions baked into its
    | UserSigns, so the submission layer sends those signers verbatim and only
    | fills the single "DIRECTOR DE LINEA" slot (the UserSigns entry with an
    | empty email) with the service line director resolved below.
    |
    |   under_150k → 3 signers  (Yiselle, Silvana, Director de Línea)
    |   150k_300k  → 4 signers  (+ Enrique Arellano)
    |   300k_2m    → 5 signers  (+ Luis Manuel Rodríguez) and the 4 extra docs
    |   over_2m    → 6 signers  (+ Gerardo Poo Gil)        and the 4 extra docs
    */
    'solicitud_templates' => [
        'under_150k' => [
            'id'        => env('CONTISIGN_TPL_SOLICITUD_MENOS150_ID', '57e36ed1-3580-4687-bce4-7a69e1644481'),
            'name'      => '00.SOLICITUD DE CRÉDITO menos de 150,000',
            'json_path' => 'contisign/templates/solicitud_menos_150.json',
        ],
        '150k_300k' => [
            'id'        => env('CONTISIGN_TPL_SOLICITUD_150A300_ID', '6c90aae4-2432-4ac6-8cb0-ed1c6e49a9a6'),
            'name'      => '00.SOLICITUD DE CRÉDITO de 150,000 a 300,000',
            'json_path' => 'contisign/templates/solicitud_150_300.json',
        ],
        '300k_2m' => [
            'id'        => env('CONTISIGN_TPL_SOLICITUD_300A2M_ID', '3a6f1b17-ea65-4721-9a8c-15e5ff911e4b'),
            'name'      => '00.SOLICITUD DE CRÉDITO más de 300,000',
            'json_path' => 'contisign/templates/solicitud_mas_300.json',
        ],
        'over_2m' => [
            'id'        => env('CONTISIGN_TPL_SOLICITUD_MAS2M_ID', '76a9756f-ec09-496f-a300-823619bd4ccc'),
            'name'      => '00.SOLICITUD DE CRÉDITO más de 2,000,000',
            'json_path' => 'contisign/templates/solicitud_mas_2m.json',
        ],
    ],

    // Fallback tier if credit_range is ever missing/unknown at submit time.
    'solicitud_default_tier' => 'under_150k',

    /*
    | Service → "Director de Línea" dynamic signer for the SOLICITUD. The
    | selected service (service_to_contract) resolves to the person who fills
    | the empty-email UserSigns slot. PLACEHOLDERS for now — swap for the real
    | line directors. 'phone' matters because the templates set requireSMS=true.
    */
    'service_line_directors' => [
        'computo'            => ['name' => 'Juan Antonio Martínez', 'email' => 'jamartinez@sistemascontino.com.mx', 'phone' => '2299000000'],
        'impresion'          => ['name' => 'Manuel Hernández', 'email' => 'mhernandez@sistemascontino.com.mx', 'phone' => '2299000000'],
        'redes'              => ['name' => 'Elias Castellanos', 'email' => 'ecastellanos@sistemascontino.com.mx', 'phone' => '2299000000'],
        'cyberseguridad'     => ['name' => 'Elias Castellanos', 'email' => 'ecastellanos@sistemascontino.com.mx', 'phone' => '2299000000'],
        'datacenter'         => ['name' => 'Elias Castellanos', 'email' => 'ecastellanos@sistemascontino.com.mx', 'phone' => '2299000000'],
        'gestion_documental' => ['name' => 'Gerardo Poo', 'email' => 'achavez@sistemascontino.com.mx', 'phone' => '2299000000'],
    ],

    // Used when service is missing/unknown so the slot is never left empty.
    'service_line_director_default' => ['name' => 'Angel Samuel', 'email' => 'gestion.documental@sistemascontino.com.mx', 'phone' => '2299000000'],

];
