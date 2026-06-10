<?php

namespace App\Http\Requests\Wizard\Individual;

use App\Http\Requests\Wizard\StepRequest;

class PersonalRequest extends StepRequest
{
    public function rules(): array
    {
        return [
            'rfc'        => ['required', 'string', 'size:13', 'regex:/^[A-ZÑ&]{4}\d{6}[A-Z\d]{3}$/i'],
            'first_name' => ['required', 'string', 'max:120'],
            'last_name'  => ['required', 'string', 'max:120'],
            'website'    => ['nullable', 'url', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('rfc')) {
            $this->merge(['rfc' => strtoupper(trim((string) $this->input('rfc')))]);
        }
    }
}
