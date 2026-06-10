<?php

namespace App\Http\Requests\Wizard\Shared;

use App\Http\Requests\Wizard\StepRequest;

class AddressRequest extends StepRequest
{
    public function rules(): array
    {
        return [
            'street'      => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'regex:/^\d{5}$/'],
            'colonia'     => ['required', 'string', 'max:160'],
            'city'        => ['required', 'string', 'max:160'],
            'state'       => ['required', 'string', 'max:160'],
            'country'     => ['required', 'string', 'max:80'],
        ];
    }
}
