{{--
| Shared — final confirmation before submitting to Contisign.
| The spec requires the user to EXPLICITLY confirm the information. This
| checkbox enables the "Confirm and submit" button (which the shell renders
| as disabled by default on the review step).
--}}
<div class="ob-card mt-4" style="background: var(--ob-surface-alt);">
    <div class="ob-card__body">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" value="1"
                id="confirm_submission" name="confirm_submission"
                data-ob-confirm required>
            <label class="form-check-label fw-semibold" for="confirm_submission">
                {{ __('I confirm that the information above is correct and complete.') }}
            </label>
        </div>
    </div>
</div>

@push('scripts')
<script type="module">
    // Enable the submit button only once the confirmation box is checked.
    const confirm = document.querySelector('[data-ob-confirm]');
    const submit = document.getElementById('ob-next-btn');
    if (confirm && submit) {
        const sync = () => {
            submit.disabled = !confirm.checked;
        };
        confirm.addEventListener('change', sync);
        sync();
    }
</script>
@endpush