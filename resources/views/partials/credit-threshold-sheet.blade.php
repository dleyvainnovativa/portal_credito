{{--
|--------------------------------------------------------------------------
| partials/credit-threshold-sheet.blade.php — ">$300,000 credit" bottom sheet
|--------------------------------------------------------------------------
| Shown once, on the first company step, to ask the credit-line question up
| front. Posts the answer to wizard.credit-threshold via fetch (no reload);
| the Documentos step reads the same flag, so its inline toggle stays the
| editable source of truth. Closing without choosing leaves the default (No)
| and the user can still set it on the Documentos step.
|
| Bottom-anchored on all sizes (wider/centered on desktop). Pure HTML/CSS/JS
| — no library. Behavior is wired by the script in wizard/shell.blade.php,
| which reads data-credit-sheet-* hooks.
|
| Rendered only when $askCreditThreshold is true (passed by WizardController).
--}}
<div class="ob-sheet"
     id="credit-threshold-sheet"
     data-credit-sheet
     data-credit-sheet-url="{{ route('wizard.credit-threshold') }}"
     role="dialog"
     aria-modal="true"
     aria-labelledby="credit-sheet-title"
     aria-describedby="credit-sheet-desc"
     hidden>

    <div class="ob-sheet__backdrop" data-credit-sheet-dismiss></div>

    <div class="ob-sheet__panel" role="document">
        <button type="button" class="ob-sheet__handle" aria-label="{{ __('Close') }}"
                data-credit-sheet-dismiss></button>

        <div class="ob-sheet__body">
            <span class="ob-sheet__icon" aria-hidden="true">
                <i class="fa-solid fa-circle-question"></i>
            </span>

            <h2 class="ob-sheet__title" id="credit-sheet-title">
                {{ __('Will your credit line be over $300,000?') }}
            </h2>

            <p class="ob-sheet__desc" id="credit-sheet-desc">
                {{ __('If so, we will later ask for 4 additional documents (annual tax returns and partial financial statements). You can change this answer on the Corporate Documents step.') }}
            </p>

            <div class="ob-sheet__actions">
                <button type="button" class="btn btn-outline-secondary ob-sheet__btn"
                        data-credit-sheet-answer="0">
                    {{ __('No') }}
                </button>
                <button type="button" class="btn btn-primary ob-sheet__btn"
                        data-credit-sheet-answer="1">
                    {{ __('Yes') }}
                </button>
            </div>

            <button type="button" class="ob-sheet__skip" data-credit-sheet-dismiss>
                {{ __('I would rather decide later') }}
            </button>
        </div>
    </div>
</div>
