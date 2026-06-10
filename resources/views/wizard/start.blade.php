{{--
|--------------------------------------------------------------------------
| wizard/start.blade.php — Applicant type selector (wizard entry point)
|--------------------------------------------------------------------------
| The first screen of the onboarding flow. Client chooses Individual
| (Persona Física) or Company (Persona Moral); selection determines which
| wizard flow renders next (wired up in Phase 1).
--}}
@extends('layouts.app')

@section('title', __('Start your application'))

@section('content')
<div class="text-center mb-4 mb-md-5 ob-reveal">
    <h1 class="h3 fw-bold mb-2">{{ __('Welcome') }}</h1>
    <p class="text-secondary mb-0" style="color: var(--ob-text-muted) !important;">
        {{ __('Choose how you would like to apply to begin onboarding.') }}
    </p>
</div>

<form method="POST" action="{{ route('wizard.begin') }}" id="ob-type-form">
    @csrf
    <div class="row g-3 g-md-4 ob-reveal" style="animation-delay: 60ms;">

        {{-- Individual --}}
        <div class="col-12 col-md-6">
            <label class="ob-card h-100 d-block p-0" role="button">
                <input class="visually-hidden" type="radio" name="applicant_type"
                    value="individual" required>
                <div class="ob-card__body text-center h-100 d-flex flex-column align-items-center justify-content-center">
                    <span class="d-inline-flex align-items-center justify-content-center mb-3"
                        style="width:56px;height:56px;border-radius:var(--ob-radius-lg);background:var(--ob-primary-soft);color:var(--ob-primary);">
                        <i class="fa-solid fa-user fa-lg" aria-hidden="true"></i>
                    </span>
                    <span class="fw-bold d-block mb-1">{{ __('Individual') }}</span>
                    <span class="text-secondary small">Persona Física</span>
                </div>
            </label>
        </div>

        {{-- Company --}}
        <div class="col-12 col-md-6">
            <label class="ob-card h-100 d-block p-0" role="button">
                <input class="visually-hidden" type="radio" name="applicant_type"
                    value="company" required>
                <div class="ob-card__body text-center h-100 d-flex flex-column align-items-center justify-content-center">
                    <span class="d-inline-flex align-items-center justify-content-center mb-3"
                        style="width:56px;height:56px;border-radius:var(--ob-radius-lg);background:var(--ob-primary-soft);color:var(--ob-primary);">
                        <i class="fa-solid fa-building fa-lg" aria-hidden="true"></i>
                    </span>
                    <span class="fw-bold d-block mb-1">{{ __('Company') }}</span>
                    <span class="text-secondary small">Persona Moral</span>
                </div>
            </label>
        </div>
    </div>

    <div class="d-grid d-sm-flex justify-content-sm-end mt-4 ob-reveal" style="animation-delay: 120ms;">
        <button type="submit" class="btn btn-primary px-4" id="ob-start-btn" disabled>
            {{ __('Continue') }}
            <i class="fa-solid fa-arrow-right ms-1" aria-hidden="true"></i>
        </button>
    </div>
</form>
@endsection

@push('scripts')
<script type="module">
    // Enable Continue once a type is chosen + highlight the selected card.
    const form = document.getElementById('ob-type-form');
    const btn = document.getElementById('ob-start-btn');
    form.addEventListener('change', () => {
        btn.disabled = !form.querySelector('input[name="applicant_type"]:checked');
        form.querySelectorAll('.ob-card').forEach((card) => {
            const input = card.querySelector('input');
            card.style.borderColor = input.checked ? 'var(--ob-primary)' : '';
            card.style.boxShadow = input.checked ? '0 0 0 4px var(--ob-primary-ring)' : '';
        });
    });
</script>
@endpush