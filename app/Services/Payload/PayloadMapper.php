<?php

namespace App\Services\Payload;

use App\Services\Wizard\WizardFlow;
use App\Services\Wizard\WizardState;

/*
|--------------------------------------------------------------------------
| PayloadMapper
|--------------------------------------------------------------------------
| Normalizes the session-held wizard data into ONE consistent structure,
| regardless of applicant type. The spec calls for this so the review screen
| and the Contisign submission layer (Phase 6) never branch on applicant
| type — they consume the same shape.
|
| Normalized shape:
| [
|   'applicant_type' => 'individual'|'company',
|   'profile' => [            // type-specific identity block, flattened
|       'rfc' => ..., 'website' => ...,
|       // individual: first_name, last_name
|       // company:    legal_name, commercial_name
|   ],
|   'representative' => [ ... ] | null,   // company only
|   'address'  => [ street, postal_code, colonia, city, state, country ],
|   'contact'  => [ sales_rep_email, first_name, last_name, email, phone ],
|   'references' => [ ['company'=>..,'phone'=>..], x3 ],
|   'documents'  => [ key => ['original_name'=>..,'stored_path'=>..,'mime'=>..,'size'=>..] ],
|   'authorization' => [ 'terms_accepted' => bool, 'accepted_at' => iso8601 ],
| ]
|
| `documents` includes identification images (id_front/id_back) alongside the
| required documents, all keyed consistently.
*/

class PayloadMapper
{
    public function __construct(private readonly WizardState $state) {}

    public function build(): array
    {
        $type = $this->state->type();

        return [
            'applicant_type' => $type,
            'profile'        => $this->profile($type),
            'representative' => $type === 'company' ? $this->representative() : null,
            'address'        => $this->address(),
            'contact'        => $this->contact(),
            'references'     => $this->references(),
            'documents'      => $this->documents($type),
            'authorization'  => $this->authorization(),
        ];
    }

    /* ----------------------------------------------------------------
     | Blocks
     | ---------------------------------------------------------------- */

    private function profile(string $type): array
    {
        if ($type === 'company') {
            $d = $this->state->stepData('business');
            return [
                'rfc'             => $d['rfc'] ?? null,
                'legal_name'      => $d['legal_name'] ?? null,
                'commercial_name' => $d['commercial_name'] ?? null,
                'website'         => $d['website'] ?? null,
            ];
        }

        $d = $this->state->stepData('personal');
        return [
            'rfc'        => $d['rfc'] ?? null,
            'first_name' => $d['first_name'] ?? null,
            'last_name'  => $d['last_name'] ?? null,
            'website'    => $d['website'] ?? null,
        ];
    }

    private function representative(): array
    {
        $d = $this->state->stepData('representative');
        return [
            'first_name' => $d['rep_first_name'] ?? null,
            'last_name'  => $d['rep_last_name'] ?? null,
            'email'      => $d['rep_email'] ?? null,
            'phone'      => $d['rep_phone'] ?? null,
            'dob'        => $d['rep_dob'] ?? null,
            'id_type'    => $d['id_type'] ?? null,
        ];
    }

    private function address(): array
    {
        $d = $this->state->stepData('address');
        return [
            'street'      => $d['street'] ?? null,
            'postal_code' => $d['postal_code'] ?? null,
            'colonia'     => $d['colonia'] ?? null,
            'city'        => $d['city'] ?? null,
            'state'       => $d['state'] ?? null,
            'country'     => $d['country'] ?? 'Mexico',
        ];
    }

    private function contact(): array
    {
        $d = $this->state->stepData('contact');
        return [
            'sales_rep_email' => $d['sales_rep_email'] ?? null,
            'first_name'      => $d['contact_first_name'] ?? null,
            'last_name'       => $d['contact_last_name'] ?? null,
            'email'           => $d['contact_email'] ?? null,
            'phone'           => $d['contact_phone'] ?? null,
        ];
    }

    private function references(): array
    {
        $refs = $this->state->stepData('references')['references'] ?? [];
        $out = [];
        foreach ($refs as $ref) {
            $out[] = [
                'company' => $ref['company'] ?? null,
                'phone'   => $ref['phone'] ?? null,
            ];
        }
        return $out;
    }

    /**
     * All uploaded documents keyed consistently. Pulls from stored file
     * metadata (set by TempFileService), so values are serializable arrays,
     * not UploadedFile instances.
     */
    private function documents(string $type): array
    {
        $files = $this->state->allFiles();
        $keys = $this->expectedDocumentKeys($type);

        $out = [];
        foreach ($keys as $key) {
            if (isset($files[$key])) {
                $out[$key] = $files[$key];
            }
        }
        return $out;
    }

    private function expectedDocumentKeys(string $type): array
    {
        // Conditional documents (credit line > $300,000) apply to both flows.
        // They are stored only when the applicant checked the box, so keys with
        // no stored file are simply skipped in documents().
        $credit = array_keys(config('documents.credit_over_threshold', []));

        if ($type === 'company') {
            return array_merge(
                array_keys(config('documents.required.company', [])),
                $credit,
                ['id_front', 'id_back'] // legal representative ID
            );
        }

        return array_merge(
            ['id_front', 'id_back'], // applicant ID
            array_keys(config('documents.required.individual', [])),
            $credit
        );
    }

    private function authorization(): array
    {
        $d = $this->state->stepData('authorization');
        return [
            'terms_accepted' => (bool) ($d['terms_accepted'] ?? false),
            'accepted_at'    => ($d['terms_accepted'] ?? false) ? now()->toIso8601String() : null,
        ];
    }
}
