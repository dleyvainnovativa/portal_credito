<?php

namespace App\Http\Requests\Wizard\Company;

use App\Http\Requests\Wizard\StepRequest;

class BusinessRequest extends StepRequest
{
    public function rules(): array
    {
        return [
            // RFC for a company (persona moral) is 12 characters.
            'rfc'             => ['required', 'string', 'size:12', 'regex:/^[A-ZÑ&]{3}\d{6}[A-Z\d]{3}$/i'],
            'legal_name'      => ['required', 'string', 'max:255'],
            'commercial_name' => ['required', 'string', 'max:255'],
            'website'         => ['nullable', 'string', 'max:255', 'regex:/^([a-z0-9-]+\.)+[a-z]{2,}$/i'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('rfc')) {
            $this->merge(['rfc' => strtoupper(trim((string) $this->input('rfc')))]);
        }
    }
}
