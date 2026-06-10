{{--
| Shared review summary — renders the normalized PayloadMapper output.
| Identical for both flows; sections that don't apply (representative) are
| simply absent from the payload. Each section links back to its step.
|
| Expects: $payload (normalized array), $flow (WizardFlow)
--}}
@php
$p = $payload ?? [];
$isCompany = ($p['applicant_type'] ?? null) === 'company';

// Helper to build a step edit URL by key.
$editUrl = fn (string $key) => route('wizard.step', ['step' => $key]);
@endphp

@unless ($p)
<div class="alert alert-warning" role="alert">
    {{ __('Could not load the summary. Please review the previous steps.') }}
</div>
@else

<div class="ob-review">

    {{-- Applicant type --}}
    <div class="d-flex align-items-center gap-2 mb-4">
        <span class="badge rounded-pill"
            style="background: var(--ob-primary-soft); color: var(--ob-primary); font-weight:600;">
            <i class="fa-solid {{ $isCompany ? 'fa-building' : 'fa-user' }} me-1" aria-hidden="true"></i>
            {{ $isCompany ? __('Company') : __('Individual') }}
        </span>
    </div>

    {{-- Profile --}}
    <section class="ob-review__section mb-4">
        <div class="ob-review__head">
            <h2 class="h6 fw-bold mb-0">{{ $isCompany ? __('Business Info') : __('Personal Info') }}</h2>
            <a href="{{ $editUrl($isCompany ? 'business' : 'personal') }}" class="ob-review__edit">
                <i class="fa-solid fa-pen" aria-hidden="true"></i> {{ __('Edit') }}
            </a>
        </div>
        <dl class="ob-review__list">
            <div>
                <dt>RFC</dt>
                <dd class="ob-mono">{{ $p['profile']['rfc'] ?? '—' }}</dd>
            </div>
            @if ($isCompany)
            <div>
                <dt>{{ __('Legal Business Name') }}</dt>
                <dd>{{ $p['profile']['legal_name'] ?? '—' }}</dd>
            </div>
            <div>
                <dt>{{ __('Commercial Name') }}</dt>
                <dd>{{ $p['profile']['commercial_name'] ?? '—' }}</dd>
            </div>
            @else
            <div>
                <dt>{{ __('First Name(s)') }}</dt>
                <dd>{{ $p['profile']['first_name'] ?? '—' }}</dd>
            </div>
            <div>
                <dt>{{ __('Last Name(s)') }}</dt>
                <dd>{{ $p['profile']['last_name'] ?? '—' }}</dd>
            </div>
            @endif
            <div>
                <dt>{{ __('Website') }}</dt>
                <dd>{{ $p['profile']['website'] ?: '—' }}</dd>
            </div>
        </dl>
    </section>

    {{-- Representative (company only) --}}
    @if ($isCompany && !empty($p['representative']))
    <section class="ob-review__section mb-4">
        <div class="ob-review__head">
            <h2 class="h6 fw-bold mb-0">{{ __('Legal Rep.') }}</h2>
            <a href="{{ $editUrl('representative') }}" class="ob-review__edit">
                <i class="fa-solid fa-pen" aria-hidden="true"></i> {{ __('Edit') }}
            </a>
        </div>
        <dl class="ob-review__list">
            <div>
                <dt>{{ __('First Name(s)') }}</dt>
                <dd>{{ $p['representative']['first_name'] ?? '—' }}</dd>
            </div>
            <div>
                <dt>{{ __('Last Name(s)') }}</dt>
                <dd>{{ $p['representative']['last_name'] ?? '—' }}</dd>
            </div>
            <div>
                <dt>{{ __('Email Address') }}</dt>
                <dd>{{ $p['representative']['email'] ?? '—' }}</dd>
            </div>
            <div>
                <dt>{{ __('Phone Number') }}</dt>
                <dd class="ob-mono">{{ $p['representative']['phone'] ?? '—' }}</dd>
            </div>
            <div>
                <dt>{{ __('Date of Birth') }}</dt>
                <dd>{{ $p['representative']['dob'] ?? '—' }}</dd>
            </div>
            <div>
                <dt>{{ __('Identification Type') }}</dt>
                <dd>{{ strtoupper($p['representative']['id_type'] ?? '—') }}</dd>
            </div>
        </dl>
    </section>
    @endif

    {{-- Address --}}
    <section class="ob-review__section mb-4">
        <div class="ob-review__head">
            <h2 class="h6 fw-bold mb-0">{{ $isCompany ? __('Business Address') : __('Address') }}</h2>
            <a href="{{ $editUrl('address') }}" class="ob-review__edit">
                <i class="fa-solid fa-pen" aria-hidden="true"></i> {{ __('Edit') }}
            </a>
        </div>
        <dl class="ob-review__list">
            <div>
                <dt>{{ __('Street and Number') }}</dt>
                <dd>{{ $p['address']['street'] ?? '—' }}</dd>
            </div>
            <div>
                <dt>{{ __('Postal Code') }}</dt>
                <dd class="ob-mono">{{ $p['address']['postal_code'] ?? '—' }}</dd>
            </div>
            <div>
                <dt>{{ __('Neighborhood (Colonia)') }}</dt>
                <dd>{{ $p['address']['colonia'] ?? '—' }}</dd>
            </div>
            <div>
                <dt>{{ __('City') }}</dt>
                <dd>{{ $p['address']['city'] ?? '—' }}</dd>
            </div>
            <div>
                <dt>{{ __('State') }}</dt>
                <dd>{{ $p['address']['state'] ?? '—' }}</dd>
            </div>
            <div>
                <dt>{{ __('Country') }}</dt>
                <dd>{{ $p['address']['country'] ?? '—' }}</dd>
            </div>
        </dl>
    </section>

    {{-- Contact --}}
    <section class="ob-review__section mb-4">
        <div class="ob-review__head">
            <h2 class="h6 fw-bold mb-0">{{ __('Primary Contact') }}</h2>
            <a href="{{ $editUrl('contact') }}" class="ob-review__edit">
                <i class="fa-solid fa-pen" aria-hidden="true"></i> {{ __('Edit') }}
            </a>
        </div>
        <dl class="ob-review__list">
            <div>
                <dt>{{ __('Sales Representative Email') }}</dt>
                <dd>{{ $p['contact']['sales_rep_email'] ?? '—' }}</dd>
            </div>
            <div>
                <dt>{{ __('First Name(s)') }}</dt>
                <dd>{{ $p['contact']['first_name'] ?? '—' }}</dd>
            </div>
            <div>
                <dt>{{ __('Last Name(s)') }}</dt>
                <dd>{{ $p['contact']['last_name'] ?? '—' }}</dd>
            </div>
            <div>
                <dt>{{ __('Email Address') }}</dt>
                <dd>{{ $p['contact']['email'] ?? '—' }}</dd>
            </div>
            <div>
                <dt>{{ __('Phone Number') }}</dt>
                <dd class="ob-mono">{{ $p['contact']['phone'] ?? '—' }}</dd>
            </div>
        </dl>
    </section>

    {{-- References --}}
    <section class="ob-review__section mb-4">
        <div class="ob-review__head">
            <h2 class="h6 fw-bold mb-0">{{ __('References') }}</h2>
            <a href="{{ $editUrl('references') }}" class="ob-review__edit">
                <i class="fa-solid fa-pen" aria-hidden="true"></i> {{ __('Edit') }}
            </a>
        </div>
        <dl class="ob-review__list">
            @forelse ($p['references'] ?? [] as $i => $ref)
            <div>
                <dt>{{ __('Reference :n', ['n' => $i + 1]) }}</dt>
                <dd>{{ $ref['company'] ?? '—' }} · <span class="ob-mono">{{ $ref['phone'] ?? '—' }}</span></dd>
            </div>
            @empty
            <div>
                <dt>—</dt>
                <dd>—</dd>
            </div>
            @endforelse
        </dl>
    </section>

    {{-- Documents --}}
    <section class="ob-review__section mb-4">
        <div class="ob-review__head">
            <h2 class="h6 fw-bold mb-0">{{ __('Required Documents') }}</h2>
            <a href="{{ $editUrl($isCompany ? 'documents' : 'identification') }}" class="ob-review__edit">
                <i class="fa-solid fa-pen" aria-hidden="true"></i> {{ __('Edit') }}
            </a>
        </div>
        <ul class="ob-review__docs">
            @forelse ($p['documents'] ?? [] as $key => $doc)
            <li>
                <i class="fa-solid fa-file-circle-check" aria-hidden="true"></i>
                <span class="ob-review__doc-key">{{ __($docLabels[$key] ?? $key) }}</span>
                <span class="ob-review__doc-name">{{ $doc['original_name'] ?? '' }}</span>
                @if (isset($doc['parts']) && count($doc['parts']) > 1)
                <span class="ob-review__doc-parts">
                    {{ __(':n parts', ['n' => count($doc['parts'])]) }}
                </span>
                @endif
            </li>
            @empty
            <li class="text-secondary">{{ __('No documents uploaded.') }}</li>
            @endforelse
        </ul>
    </section>

</div>
@endunless