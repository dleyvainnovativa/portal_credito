<?php

namespace App\Http\Requests\Wizard\Shared;

use App\Http\Requests\Wizard\StepRequest;

/*
| Primary Contact — shared by both flows.
| Enforces the email-confirmation match rule via `confirmed`: the field
| `contact_email` must be matched by `contact_email_confirmation`.
*/

class ContactRequest extends StepRequest
{
    public function rules(): array
    {
        return [
            'sales_rep_email'    => ['required', 'email', 'max:255'],
            'contact_first_name' => ['required', 'string', 'max:120'],
            'contact_last_name'  => ['required', 'string', 'max:120'],
            'contact_email'      => ['required', 'email', 'max:255', 'confirmed'],
            'contact_phone'      => ['required', 'string', 'regex:/^[\d\s\-\+\(\)]{7,20}$/', 'confirmed'],
        ];
    }
}
