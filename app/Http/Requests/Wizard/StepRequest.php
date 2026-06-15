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

    /**
     * Normalize a website value to a bare domain: strip any scheme
     * (http/https), leading "www.", trailing slash, and whitespace. So a
     * pasted "https://www.innovativa.mx/" becomes "innovativa.mx".
     * Returns null for empty input.
     */
    protected function normalizeDomain(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        $value = preg_replace('#^[a-z][a-z0-9+.\-]*://#i', '', $value); // scheme
        $value = preg_replace('#^www\.#i', '', $value);                  // www.
        $value = rtrim($value, '/');                                     // trailing slash
        // Drop any path/query if a full URL was pasted (keep host only).
        $value = preg_replace('#[/?#].*$#', '', $value);
        return strtolower(trim($value));
    }

    /**
     * Validation rule for a bare domain (no scheme): one or more labels
     * followed by a TLD of 2+ letters. Accepts innovativa.mx,
     * sistemascontino.com.mx, sub.domain.com.
     */
    protected function domainRule(): string
    {
        return 'regex:/^(?=.{1,253}$)([a-z0-9](?:[a-z0-9\-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/i';
    }
}
