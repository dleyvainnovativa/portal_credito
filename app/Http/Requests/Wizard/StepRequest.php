<?php

namespace App\Http\Requests\Wizard;

use Illuminate\Foundation\Http\FormRequest;

/*
|--------------------------------------------------------------------------
| StepRequest (base)
|--------------------------------------------------------------------------
| Shared base for every wizard step's validation. The public onboarding
| flow has no auth gate, so authorize() is always true. Subclasses define
| rules() and may override messages()/attributes() — though most messages
| come from the Spanish validation lang file.
|
| Subclasses live in this namespace and are mapped to step keys by
| StepRequestResolver.
*/

abstract class StepRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Only the validated step fields are persisted to wizard state, so we
     * strip framework fields here for safety.
     */
    public function validated($key = null, $default = null): array
    {
        $data = parent::validated();
        unset($data['_token'], $data['_method'], $data['direction']);
        return $data;
    }
}
