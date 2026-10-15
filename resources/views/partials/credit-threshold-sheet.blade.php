{{--
|--------------------------------------------------------------------------
| partials/credit-threshold-sheet.blade.php — intake bottom sheet
|--------------------------------------------------------------------------
| Shown once, on the first step of either flow. A required two-step intake
| the user must complete (or leave via "Regresarme"); it cannot be dismissed
| (no backdrop click, no Esc, no close handle).
|
|   Step 1 — Aviso de Privacidad. The user must tick the checkbox to continue.
|   Step 2 — Credit line range (3 styled radio options) + service to contract
|            (select). Selecting "Más de $300,000" maps to the existing
|            credit_over_threshold flag, so the credit-docs step (Documentos /
|            Documentación de Soporte) still toggles its extra uploads.
|
| On "Continuar" (step 2) the answer is posted to wizard.credit-threshold via
| fetch (no reload) as credit_over_threshold = 1 when the ">$300,000" tier is
| chosen, else 0. The selected range and service are visual-only for now
| (no backend persistence yet — to be wired later).
|
| Behavior is driven by the script in wizard/shell.blade.php via the
| data-credit-sheet-* hooks. Rendered only when $askCreditThreshold is true.
--}}
<div class="ob-sheet"
    id="credit-threshold-sheet"
    data-credit-sheet
    data-credit-sheet-url="{{ route('wizard.credit-threshold') }}"
    role="dialog"
    aria-modal="true"
    aria-labelledby="credit-sheet-title"
    hidden>

    <div class="ob-sheet__backdrop"></div>

    <div class="ob-sheet__panel" role="document">
        <span class="ob-sheet__handle" aria-hidden="true"></span>



        {{-- ========================= Step 1 — Privacy ========================= --}}
        <div class="ob-sheet__body" data-sheet-step="privacy">
            <span class="ob-sheet__icon" aria-hidden="true">
                <i class="fa-solid fa-shield-halved"></i>
            </span>

            <h2 class="ob-sheet__title" id="credit-sheet-title">
                {{ __('Privacy Notice') }}
            </h2>

            <p class="ob-sheet__desc">
                {{ __('To continue, you must accept the Privacy Notice.') }}
            </p>

            <label class="ob-check" for="privacy_accept">
                <input class="ob-check__input" type="checkbox" value="1"
                    id="privacy_accept" name="privacy_accept"
                    data-credit-sheet-privacy>
                <span class="ob-check__box" aria-hidden="true">
                    <i class="fa-solid fa-check"></i>
                </span>
                <span class="ob-check__label">
                    {{ __('I have read and accept the') }}
                    <a href="{{ config('branding.privacy_url', '#') }}" target="_blank" rel="noopener">{{ __('Privacy Notice') }}</a>.
                </span>
            </label>

            <div class="ob-sheet__actions">
                <button type="button" class="btn btn-primary ob-sheet__btn"
                    data-credit-sheet-next disabled>
                    {{ __('Continue') }}
                </button>
            </div>

            <a href="{{ route('wizard.start') }}" class="ob-sheet__skip">
                {{ __('Go back') }}
            </a>
        </div>

        {{-- ==================== Step 2 — Credit range + service ==================== --}}
        <div class="ob-sheet__body" data-sheet-step="credit" hidden>
            <span class="ob-sheet__icon" aria-hidden="true">
                <i class="fa-solid fa-coins"></i>
            </span>

            <h2 class="ob-sheet__title">
                {{ __('What credit line amount will you need?') }}
            </h2>

            <p class="ob-sheet__desc">
                {{ __('If you select more than $300,000, we will later ask for 4 additional documents (annual tax returns and partial financial statements).') }}
            </p>

            <fieldset class="ob-choices" data-credit-sheet-range>
                <legend class="visually-hidden">{{ __('What credit line amount will you need?') }}</legend>

                <label class="ob-choice">
                    <input class="ob-choice__input" type="radio" name="credit_range"
                        value="under_150k" data-credit-over="0">
                    <span class="ob-choice__box">
                        <span class="ob-choice__label">{{ __('Less than $150,000') }}</span>
                    </span>
                </label>

                <label class="ob-choice">
                    <input class="ob-choice__input" type="radio" name="credit_range"
                        value="150k_300k" data-credit-over="0">
                    <span class="ob-choice__box">
                        <span class="ob-choice__label">{{ __('$150,000 to $300,000') }}</span>
                    </span>
                </label>

                <label class="ob-choice">
                    <input class="ob-choice__input" type="radio" name="credit_range"
                        value="over_300k" data-credit-over="1">
                    <span class="ob-choice__box">
                        <span class="ob-choice__label">{{ __('More than $300,000') }}</span>
                    </span>
                </label>
            </fieldset>

            <div class="ob-field">
                <label class="ob-field__label" for="service_to_contract">
                    {{ __('Service to contract') }}
                </label>
                <select class="form-select" id="service_to_contract" name="service_to_contract"
                    data-credit-sheet-service>
                    <option value="" selected disabled>{{ __('Select a service') }}</option>
                    <option value="computo">Cómputo</option>
                    <option value="impresion">Impresión</option>
                    <option value="redes">Redes</option>
                    <option value="datacenter">Datacenter</option>
                    <option value="ciberseguridad">Ciberseguridad</option>
                    <option value="gestion_documental">Gestión Documental</option>
                </select>
            </div>

            <div class="ob-sheet__actions">
                <button type="button" class="btn btn-outline-secondary ob-sheet__btn"
                    data-credit-sheet-back>
                    {{ __('Back') }}
                </button>
                <button type="button" class="btn btn-primary ob-sheet__btn"
                    data-credit-sheet-submit disabled>
                    {{ __('Continue') }}
                </button>
            </div>

            <a href="{{ route('wizard.start') }}" class="ob-sheet__skip">
                {{ __('Go back') }}
            </a>
        </div>
        {{-- Step progress dots --}}
        <div class="ob-sheet__steps" aria-hidden="true">
            <span class="ob-sheet__dot is-active" data-sheet-dot="privacy"></span>
            <span class="ob-sheet__dot" data-sheet-dot="credit"></span>
        </div>
    </div>
</div>