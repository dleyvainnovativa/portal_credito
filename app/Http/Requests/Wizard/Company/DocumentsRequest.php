<?php

namespace App\Http\Requests\Wizard\Company;

use App\Http\Requests\Wizard\StepRequest;
use Illuminate\Validation\Rule;

/*
| Company · Step 3 — Corporate Documents
| Four required documents plus, when the >$300,000 credit box is checked, four
| conditional documents. A "{key}_exists" hidden flag lets users who already
| uploaded (then navigated back) proceed without re-selecting the file.
*/

class DocumentsRequest extends StepRequest
{
    public function rules(): array
    {
        $maxKb = (int) config('contisign.annex.max_size_kb', 15360);
        $mimes = implode(',', config('contisign.annex.mimes', ['pdf', 'jpg', 'jpeg', 'png']));

        $rules = [];

        // UI-only gate; not persisted to Contisign.
        $rules['credit_over_threshold'] = ['nullable', 'boolean'];

        // Always-required corporate documents.
        foreach (array_keys(config('documents.required.company', [])) as $key) {
            $rules[$key] = [
                Rule::requiredIf(fn() => ! $this->boolean("{$key}_exists")),
                'nullable',
                'array',
            ];
            $rules["$key.*"] = ['file', "mimes:$mimes", "max:$maxKb"];
        }

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
