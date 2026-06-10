<?php

namespace App\Http\Requests\Wizard\Shared;

use App\Http\Requests\Wizard\StepRequest;

/*
| Credit Authorization — shared by both flows. The Terms & Conditions
| checkbox must be accepted before the user can proceed to review.
*/

class AuthorizationRequest extends StepRequest
{
    public function rules(): array
    {
        return [
            'terms_accepted' => ['accepted'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Normalize unchecked checkbox (absent) to a definite value.
        $this->merge([
            'terms_accepted' => $this->boolean('terms_accepted'),
        ]);
    }
}
