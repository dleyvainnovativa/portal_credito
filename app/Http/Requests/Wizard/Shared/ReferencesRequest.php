<?php

namespace App\Http\Requests\Wizard\Shared;

use App\Http\Requests\Wizard\StepRequest;

/*
| Commercial References — shared by both flows. Three references, each with
| a company name and phone number. Field naming: references.{0,1,2}.company
| and references.{0,1,2}.phone (array input).
*/

class ReferencesRequest extends StepRequest
{
    public function rules(): array
    {
        return [
            'references'           => ['required', 'array', 'size:3'],
            'references.*.company' => ['required', 'string', 'max:200'],
            'references.*.phone'   => ['required', 'string', 'regex:/^[\d\s\-\+\(\)]{7,20}$/'],
        ];
    }
}
