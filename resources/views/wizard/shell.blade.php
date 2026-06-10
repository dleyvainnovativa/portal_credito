{{--
|--------------------------------------------------------------------------
| wizard/shell.blade.php — Wizard step wrapper
|--------------------------------------------------------------------------
| Common chrome around every step: progress stepper, the step's own partial,
| and the Back / Continue navigation. Each step partial only renders its own
| fields inside the surrounding <form>.
|
| Provided by WizardController@show:
|   $flow, $step, $position, $definition, $partial, $data,
|   $isFirst, $isLast, $stepLabels
--}}
@extends('layouts.app')

@section('title', $definition['label'])

@section('side-image', $position)

@section('header-actions')
    <form method="POST" action="{{ route('wizard.cancel') }}"
          onsubmit="return confirm('{{ __('Discard this application and start over?') }}');">
        @csrf
        <button type="submit" class="btn btn-sm btn-outline-secondary">
            <i class="fa-solid fa-xmark me-1" aria-hidden="true"></i>{{ __('Cancel') }}
        </button>
    </form>
@endsection

@section('content')
    <div class="ob-reveal">
        @include('partials.stepper', ['steps' => $stepLabels, 'current' => $position])
    </div>

    {{-- Flash messages --}}
    @foreach (['warning' => 'warning', 'info' => 'info', 'success' => 'success'] as $key => $variant)
        @if (session($key))
            <div class="alert alert-{{ $variant === 'info' ? 'primary' : $variant }} d-flex align-items-center gap-2"
                 role="alert">
                <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                <span>{{ session($key) }}</span>
            </div>
        @endif
    @endforeach

    {{-- Validation summary (Phase 2 populates this) --}}
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <div class="d-flex align-items-center gap-2 mb-1 fw-semibold">
                <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                {{ __('Please fix the following:') }}
            </div>
            <ul class="mb-0 ps-4">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="ob-card ob-reveal" style="animation-delay: 60ms;">
        <div class="ob-card__body">
            <div class="mb-4">
                <h1 class="h4 fw-bold mb-1">{{ $definition['label'] }}</h1>
                <p class="mb-0 small" style="color: var(--ob-text-muted);">
                    {{ __('Step :n of :t', ['n' => $position, 't' => $flow->count()]) }}
                </p>
            </div>

            <form method="POST"
                  action="{{ $isLast ? route('wizard.submit') : route('wizard.next', ['step' => $step]) }}"
                  id="ob-step-form" enctype="multipart/form-data" novalidate>
                @csrf

                {{-- The step's own fields. Every step now has a real partial;
                     the placeholder only shows if a partial is genuinely
                     missing (single source of truth — no double lookup). --}}
                @if (view()->exists($partial))
                    @include($partial, ['data' => $data, 'flow' => $flow, 'files' => $files ?? [], 'payload' => $payload ?? null])
                @else
                    <div class="text-center py-5" style="color: var(--ob-text-subtle);">
                        <i class="fa-regular fa-pen-to-square fa-2x mb-3 d-block" aria-hidden="true"></i>
                        <p class="mb-0">{{ $definition['label'] }}</p>
                        <p class="small mb-0">{{ __('This step is not available yet.') }}</p>
                    </div>
                @endif

                {{-- Navigation --}}
                <div class="d-flex justify-content-between align-items-center mt-4 pt-3"
                     style="border-top: 1px solid var(--ob-border);">
                    @if ($isFirst)
                        <span></span>
                    @else
                        <a href="{{ route('wizard.back', ['step' => $step]) }}"
                           class="btn btn-outline-secondary">
                            <i class="fa-solid fa-arrow-left me-1" aria-hidden="true"></i>{{ __('Back') }}
                        </a>
                    @endif

                    <button type="submit" class="btn btn-primary px-4" id="ob-next-btn"
                            @if ($isLast) disabled @endif>
                        @if ($isLast)
                            <i class="fa-solid fa-paper-plane me-1" aria-hidden="true"></i>{{ __('Confirm and submit') }}
                        @else
                            {{ __('Continue') }}
                            <i class="fa-solid fa-arrow-right ms-1" aria-hidden="true"></i>
                        @endif
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script type="module">
    // Show a loading state on submit (uses the OB helper from app.js).
    const form = document.getElementById('ob-step-form');
    form?.addEventListener('submit', () => {
        if (window.OB) window.OB.setLoading('#ob-next-btn', true, '{{ __('Saving…') }}');
    });
</script>
@endpush
