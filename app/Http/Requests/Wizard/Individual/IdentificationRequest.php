<?php

namespace App\Http\Requests\Wizard\Individual;

use App\Http\Requests\Wizard\StepRequest;
use Illuminate\Validation\Rule;

/*
| Individual · Step 3 — Identification & Documents
| Conditional rule: INE requires front + back images; Passport / Immigration
| require only the front. Required documents (proof of address, tax cert) are
| enforced here too. File constraints come from config/documents.php.
|
| Note: on "back" navigation a previously uploaded file may already be held by
| TempFileService (Phase 4); the `required` rules use `required_without` against
| a hidden field that marks an existing upload so users aren't forced to
| re-select files they already provided.
*/

class IdentificationRequest extends StepRequest
{
    public function rules(): array
    {
        $maxKb = (int) config('contisign.annex.max_size_kb', 15360);
        $mimes = implode(',', config('contisign.annex.mimes', ['pdf', 'jpg', 'jpeg', 'png']));

        $isIne = $this->input('id_type') === 'ine';

        return [
            'id_type' => ['required', Rule::in(['ine', 'passport', 'immigration'])],

            'id_front'   => [
                Rule::requiredIf(fn() => ! $this->boolean('id_front_exists')),
                'nullable',
                'array',
            ],
            'id_front.*' => ['file', "mimes:$mimes", "max:$maxKb"],

            'id_back'    => [
                Rule::requiredIf(fn() => $isIne && ! $this->boolean('id_back_exists')),
                'nullable',
                'array',
            ],
            'id_back.*'  => ['file', "mimes:$mimes", "max:$maxKb"],

            'proof_of_address'   => [
                Rule::requiredIf(fn() => ! $this->boolean('proof_of_address_exists')),
                'nullable',
                'array',
            ],
            'proof_of_address.*' => ['file', "mimes:$mimes", "max:$maxKb"],

            'tax_certificate'    => [
                Rule::requiredIf(fn() => ! $this->boolean('tax_certificate_exists')),
                'nullable',
                'array',
            ],
            'tax_certificate.*'  => ['file', "mimes:$mimes", "max:$maxKb"],
        ];
    }
}
