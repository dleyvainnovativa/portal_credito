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

        'solicitud' => [
            'id'         => env('CONTISIGN_TPL_SOLICITUD_ID', '626e9807-2070-4d89-ae52-acb5bbe15557'),
            'name'       => '00.SOLICITUD DE CRÉDITO (PORTAL)',
            'json_path'  => 'contisign/templates/solicitud_credito.json',
            // 'signer'     => [
            //     'name'  => 'Daniel',
            //     'email' => 'dancaballerodlc@gmail.com',
            //     'phone' => '2291645189',
            // ],
            'signer'     => [
                'name'  => 'Silvana',
                'email' => 'silvanal@sistemascontino.com.mx',
                'phone' => '2299581884',
            ],
        ],

        'buro' => [
            'id'        => env('CONTISIGN_TPL_BURO_ID', '61ee9d8a-1acb-4349-b190-fd125995d676'),
            'name'      => '00.BURO DE CREDITO (con Correo institucional)',
            'json_path' => 'contisign/templates/buro_credito.json',
            'signer'    => 'client', // client name/email/phone from the contact step
        ],
    ],

];
