<?php

namespace App\Http\Requests\Wizard\Individual;

use App\Http\Requests\Wizard\StepRequest;
use Illuminate\Validation\Rule;

/*
| Individual · Step 3 — Identification & Documents
| INE requires front + back images; Passport / Immigration require only front.
| Required docs (proof of address, tax cert) enforced here. When the >$300,000
| credit box is checked, four conditional documents become required too.
|
| On "back" navigation a previously uploaded file may already be held by
| TempFileService; the `{key}_exists` hidden field lets those rules pass without
| re-selecting.
*/

class IdentificationRequest extends StepRequest
{
    public function rules(): array
    {
        $maxKb = (int) config('contisign.annex.max_size_kb', 15360);
        $mimes = implode(',', config('contisign.annex.mimes', ['pdf', 'jpg', 'jpeg', 'png']));
        $isIne = $this->input('id_type') === 'ine';

        $rules = [
            'id_type' => ['required', Rule::in(['ine', 'passport', 'immigration'])],

            'credit_over_threshold' => ['nullable', 'boolean'],

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

        // Conditional documents — required only when the box is checked AND
        // nothing is already stored for that key.
        foreach (array_keys(config('documents.credit_over_threshold', [])) as $key) {
            $rules[$key] = [
                Rule::requiredIf(fn() => $this->boolean('credit_over_threshold')
                    && ! $this->boolean("{$key}_exists")),
                'nullable',
                'array',
            ];
            $rules["$key.*"] = ['file', "mimes:$mimes", "max:$maxKb"];
        }

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'credit_over_threshold' => $this->boolean('credit_over_threshold'),
        ]);
    }
}
