{{--
| partials/legal/integral-accept.blade.php
| Review-step block: full Aviso de Privacidad Integral (inline, scrollable)
| plus the second, obligatory acceptance checkbox. Together with
| confirm_submission it gates the "Confirm and submit" button (see the script
| in shared/confirm-submit.blade.php).
--}}
<section class="ob-card mt-4">
    <div class="ob-card__body">
        <h2 class="h6 fw-bold mb-3">{{ __('Aviso de Privacidad Integral') }}</h2>

        <div class="ob-legal-box ob-legal-box--tall">
            @include('partials.legal.aviso-integral')
        </div>

        <label class="ob-check mt-3" for="aviso_integral_accepted">
            <input class="ob-check__input" type="checkbox" value="1"
                   id="aviso_integral_accepted" name="aviso_integral_accepted"
                   data-ob-integral-accept required>
            <span class="ob-check__box" aria-hidden="true">
                <i class="fa-solid fa-check"></i>
            </span>
            <span class="ob-check__label">
                He leído el Aviso de Privacidad Integral de Sistemas Contino.
                <span class="ob-check__req">({{ __('Obligatoria') }})</span>
            </span>
        </label>
    </div>
</section>
