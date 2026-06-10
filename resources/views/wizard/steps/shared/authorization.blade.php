{{--
| Shared — Credit Authorization (used by both flows).
| The T&C checkbox must be accepted (`accepted` rule) to proceed.
--}}
<div class="mb-2">
    <h2 class="h6 fw-bold mb-3">{{ __('Credit Authorization') }}</h2>

    <p>Términos y condiciones, cláusulas de medios electrónicos tales como NIP.
        Autorizo expresamente a <a class="fw-bold" data-bs-toggle="tooltip" data-bs-html="true" data-bs-title="<em>Tooltip</em> <u>with</u> <b>HTML</b>">Dun & Bradstreet, S.A. SIC.</a>, para que lleve a cabo investigaciones sobre mi comportamiento Crediticio en las Sociedades de Información Crediticia (SIC), y otras fuentes de información que estime conveniente. Conozco la naturaleza y alcance de la información que se solicitará, el uso que se le dará, que la misma podrá ser trasladada a terceros, y que se podrán realizar consultas periódicas sobre mi historial y otras fuentes. Consiento que esta autorización tenga una vigencia de 3 años contados a partir de hoy, y en su caso mientras mantengamos relación jurídica.</p>

    <p>Acepto que este documento quede bajo propiedad de <a class="fw-bold" data-bs-toggle="tooltip" data-bs-html="true" data-bs-title="<em>Tooltip</em> <u>with</u> <b>HTML</b>">Dun & Bradstreet, S.A. SIC.</a></p>

    <div class="form-check p-3 rounded"
        style="background: var(--ob-surface-alt); border: 1px solid var(--ob-border);">
        <input class="form-check-input" type="checkbox" value="1"
            id="terms_accepted" name="terms_accepted"
            {{ old('terms_accepted', $data['terms_accepted'] ?? false) ? 'checked' : '' }} required>
        <label class="form-check-label" for="terms_accepted">
            {{ __('I have read and accept the Terms and Conditions and authorize the credit inquiry.') }}
        </label>
    </div>
</div>