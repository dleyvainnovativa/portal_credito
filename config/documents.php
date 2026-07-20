<?php

/*
|--------------------------------------------------------------------------
| Documents configuration
|--------------------------------------------------------------------------
| Configurable upload rules and the required-document sets per applicant
| type. Used by validation (Phase 2/4) and TempFileService (Phase 4).
*/

return [

    // Temporary storage disk + directory. Files live here only during the
    // wizard session and are purged after a successful Contisign submission
    // (and swept periodically for abandoned sessions).
    'temp_disk' => env('DOCUMENTS_TEMP_DISK', 'local'),
    'temp_dir'  => 'onboarding/tmp',

    // Abandoned temp upload directories older than this are swept by the
    // scheduled `uploads:purge` command.
    'temp_ttl_minutes' => env('DOCUMENTS_TEMP_TTL_MINUTES', 180),

    // Global upload constraints.
    'max_size_kb'  => env('DOCUMENTS_MAX_SIZE_KB', 81920), // 8 MB
    'mimes'        => ['pdf', 'jpg', 'jpeg', 'png'],

    // Required documents keyed by applicant type. Keys are stable slugs used
    // across validation, temp storage, the review screen, and payload mapping.
    'required' => [

        'individual' => [
            'proof_of_address' => 'Proof of Address',
            'tax_certificate'  => 'Tax Registration Certificate',
        ],

        'company' => [
            'articles_of_incorporation' => 'Articles of Incorporation',
            'power_of_attorney'         => 'Power of Attorney Documentation',
            'proof_of_address'          => 'Proof of Address',
            'tax_certificate'           => 'Tax Registration Certificate',
        ],
    ],

    // Conditional documents: only required when the applicant declares a credit
    // line over $300,000 (UI checkbox). Same shape as `required.*` (key => label).
    // Uploaded as annexes exactly like the required ones. Applies to BOTH flows.
    'credit_over_threshold' => [
        'annual_return_1'      => 'Latest Annual Tax Return',
        'annual_return_2'      => 'Previous Annual Tax Return',
        'financials_partial_1' => 'Partial Financial Statements (page 1)',
        'financials_partial_2' => 'Partial Financial Statements (page 2)',
    ],

    // Identification images, gated by selected identification type.
    'identification' => [
        'ine'         => ['front' => 'INE Front', 'back' => 'INE Back'],
        'passport'    => ['front' => 'Passport'],
        'immigration' => ['front' => 'Immigration Document'],
    ],

];
