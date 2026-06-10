<?php

namespace App\Http\Requests\Wizard\Company;

use App\Http\Requests\Wizard\StepRequest;
use Illuminate\Validation\Rule;

/*
| Company · Step 4 — Legal Representative
| Personal details + email confirmation + date of birth, plus the same
| conditional identification rule as the individual flow (INE → front+back,
| otherwise front only).
*/

class RepresentativeRequest extends StepRequest
{
    public function rules(): array
    {
        $maxKb = (int) config('contisign.annex.max_size_kb', 15360);
        $mimes = implode(',', config('contisign.annex.mimes', ['pdf', 'jpg', 'jpeg', 'png']));
        $isIne = $this->input('id_type') === 'ine';

        return [
            'rep_first_name' => ['required', 'string', 'max:120'],
            'rep_last_name'  => ['required', 'string', 'max:120'],
            'rep_email'      => ['required', 'email', 'max:255', 'confirmed'],
            'rep_phone'      => ['required', 'string', 'regex:/^[\d\s\-\+\(\)]{7,20}$/', 'confirmed'],
            'rep_dob'        => ['required', 'date', 'before:today'],

            'id_type' => ['required', Rule::in(['ine', 'passport', 'immigration'])],
            'id_front'   => [
                Rule::requiredIf(fn() => ! $this->boolean('id_front_exists')),
                'nullable',
                'array',
            ],
            'id_front.*' => ['file', "mimes:$mimes", "max:$maxKb"],
            'id_back'    => [
                Rule::requiredIf(fn() => $isIne && ! $this->boolean('id_back_exists')),
                'nullable',
                'array',
            ],
            'id_back.*'  => ['file', "mimes:$mimes", "max:$maxKb"],
        ];
    }
}
