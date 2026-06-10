<?php

namespace App\Http\Requests\Wizard\Company;

use App\Http\Requests\Wizard\StepRequest;
use Illuminate\Validation\Rule;

/*
| Company · Step 3 — Corporate Documents
| Four required documents. As with the individual flow, a "{key}_exists"
| hidden flag lets users who already uploaded (then navigated back) proceed
| without re-selecting the file.
*/

class DocumentsRequest extends StepRequest
{
    public function rules(): array
    {
        $maxKb = (int) config('contisign.annex.max_size_kb', 15360);
        $mimes = implode(',', config('contisign.annex.mimes', ['pdf', 'jpg', 'jpeg', 'png']));

        $rules = [];
        foreach (array_keys(config('documents.required.company', [])) as $key) {
            // Each document is an array of one or more parts (split client-side).
            $rules[$key]     = [
                Rule::requiredIf(fn() => ! $this->boolean("{$key}_exists")),
                'nullable',
                'array',
            ];
            $rules["$key.*"] = ['file', "mimes:$mimes", "max:$maxKb"];
        }

        return $rules;
    }
}
