<?php

namespace App\Http\Requests\Wizard\Company;

use App\Http\Requests\Wizard\StepRequest;

/*
| Company (Persona Moral) · Primary Contact
| Reduced from the individual flow: no email/phone confirmation.
*/

class ContactRequest extends StepRequest
{
    public function rules(): array
    {
        return [
            'sales_rep_email'    => ['required', 'email', 'max:255'],
            'contact_first_name' => ['required', 'string', 'max:120'],
            'contact_last_name'  => ['required', 'string', 'max:120'],
            'contact_email'      => ['required', 'email', 'max:255'],
            'contact_phone'      => ['required', 'string', 'regex:/^[\d\s\-\+\(\)]{7,20}$/'],
        ];
    }
}
