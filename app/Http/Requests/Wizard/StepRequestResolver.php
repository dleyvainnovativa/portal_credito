<?php

namespace App\Http\Requests\Wizard;

/*
|--------------------------------------------------------------------------
| StepRequestResolver
|--------------------------------------------------------------------------
| Maps "{type}.{stepKey}" to a concrete StepRequest class. Steps that carry
| no input (e.g. the review step) map to null and skip validation.
|
| Shared steps (address, contact, references, authorization) reuse the same
| request class across both flows to avoid duplication.
*/

class StepRequestResolver
{
    /** @var array<string, class-string<StepRequest>|null> */
    private const MAP = [
        // Individual
        'individual.personal'       => Individual\PersonalRequest::class,
        'individual.address'        => Shared\AddressRequest::class,
        'individual.identification' => Individual\IdentificationRequest::class,
        'individual.contact'        => Shared\ContactRequest::class,
        'individual.references'     => Shared\ReferencesRequest::class,
        'individual.authorization'  => Shared\AuthorizationRequest::class,
        'individual.review'         => null,

        // Company
        'company.business'          => Company\BusinessRequest::class,
        'company.address'           => Shared\AddressRequest::class,
        'company.documents'         => Company\DocumentsRequest::class,
        'company.representative'    => Company\RepresentativeRequest::class,
        'company.contact'           => Shared\ContactRequest::class,
        'company.references'        => Shared\ReferencesRequest::class,
        'company.authorization'     => Shared\AuthorizationRequest::class,
        'company.review'            => null,
    ];

    /** @return class-string<StepRequest>|null */
    public static function resolve(string $type, string $stepKey): ?string
    {
        return self::MAP["$type.$stepKey"] ?? null;
    }
}
