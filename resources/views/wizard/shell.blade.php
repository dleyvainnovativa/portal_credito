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

@section('side-image', ($flow->type === 'company' ? 'moral' : 'fisica') . '/' . $position)

@section('header-actions')
    {{-- Opens the confirmation modal instead of a native confirm() dialog.
         The modal itself is pushed to the body-level 'modals' stack below. --}}
    <button type="button" class="btn btn-sm btn-outline-secondary"
            data-bs-toggle="modal" data-bs-target="#cancel-modal">
        <i class="fa-solid fa-xmark me-1" aria-hidden="true"></i>{{ __('Cancel') }}
    </button>
@endsection

{{-- Discard-application confirmation, rendered at the end of <body> so its
     backdrop covers the whole viewport and locks all interaction. The
     "discard" button submits the wizard.cancel form; dismissing keeps the
     in-progress application. --}}
@push('modals')
    <div class="modal fade" id="cancel-modal" tabindex="-1"
         aria-labelledby="cancel-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cancel-modal-title">{{ __('Cancel') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="{{ __('Close') }}"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">{{ __('Discard this application and start over?') }}</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        {{ __('No, keep my application') }}
                    </button>
                    <form method="POST" action="{{ route('wizard.cancel') }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-danger">
                            <i class="fa-solid fa-xmark me-1" aria-hidden="true"></i>{{ __('Yes, discard') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endpush

@section('content')
    {{-- Up-front ">$300,000 credit" question (company flow, first step, once). --}}
    @if (!empty($askCreditThreshold))
        @include('partials.credit-threshold-sheet')
    @endif

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

    /* ----------------------------------------------------------------
     | Credit-threshold bottom sheet
     | ----------------------------------------------------------------
     | Auto-opens when the server rendered it (first company step, unanswered).
     | Sí/No post the answer via fetch and close; closing any other way leaves
     | the default (No) and is still changeable on the Documentos step. The
     | server won't render it again this session once answered, so there's no
     | re-pop on Back. */
    (function () {
        const sheet = document.querySelector('[data-credit-sheet]');
        if (!sheet) return;

        const url   = sheet.dataset.creditSheetUrl;
        const token = document.querySelector('meta[name="csrf-token"]')?.content;
        const panel = sheet.querySelector('.ob-sheet__panel');
        let lastFocus = null;

        const open = () => {
            lastFocus = document.activeElement;
            sheet.hidden = false;
            document.body.classList.add('ob-sheet-open');
            // next frame so the transition runs from the hidden state
            requestAnimationFrame(() => {
                sheet.classList.add('is-open');
                (panel.querySelector('[data-credit-sheet-answer="1"]') || panel).focus();
            });
        };

        const hide = () => {
            sheet.classList.remove('is-open');
            document.body.classList.remove('ob-sheet-open');
            const done = () => { sheet.hidden = true; panel.removeEventListener('transitionend', done); };
            panel.addEventListener('transitionend', done);
            // fallback if transitionend doesn't fire (reduced motion)
            setTimeout(() => { if (!sheet.classList.contains('is-open')) sheet.hidden = true; }, 350);
            if (lastFocus && lastFocus.focus) lastFocus.focus();
        };

        const post = (payload) => {
            // fire-and-forget; the UI closes regardless of the result
            try {
                return fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(payload),
                });
            } catch (e) {
                if (window.console) console.warn('credit-threshold save failed', e);
            }
        };

        let settled = false;   // ensure we record exactly one outcome
        const answer = (value) => { if (settled) return; settled = true; post({ credit_over_threshold: value ? 1 : 0 }); hide(); };
        // Dismiss still records "asked" so the sheet doesn't re-pop on later
        // steps; it leaves the value at its default (No), changeable on-step.
        const dismiss = () => { if (settled) return; settled = true; post({ dismissed: 1 }); hide(); };

        // Wire controls
        sheet.querySelectorAll('[data-credit-sheet-answer]').forEach((btn) => {
            btn.addEventListener('click', () => answer(btn.dataset.creditSheetAnswer === '1'));
        });
        sheet.querySelectorAll('[data-credit-sheet-dismiss]').forEach((el) => {
            el.addEventListener('click', dismiss);
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !sheet.hidden) dismiss();
        });
        // Simple focus trap inside the panel
        sheet.addEventListener('keydown', (e) => {
            if (e.key !== 'Tab' || sheet.hidden) return;
            const f = panel.querySelectorAll('button:not([disabled])');
            if (!f.length) return;
            const first = f[0], last = f[f.length - 1];
            if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
            else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
        });

        open();
    })();
</script>
@endpush