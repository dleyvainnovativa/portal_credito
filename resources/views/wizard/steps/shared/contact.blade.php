{{--
| Shared — Primary Contact fields (used by both flows).
| contact_email + contact_email_confirmation drive the `confirmed` rule.
--}}
<div class="mb-4">
    <label for="sales_rep_email" class="form-label">{{ __('Sales Representative Email') }}<span class="ob-req">*</span></label>
    <input type="email" class="form-control" id="sales_rep_email" name="sales_rep_email"
        value="{{ old('sales_rep_email', $data['sales_rep_email'] ?? '') }}" required>
</div>

<div class="row g-3">
    <div class="col-12 col-sm-6">
        <label for="contact_first_name" class="form-label">{{ __('First Name(s)') }}<span class="ob-req">*</span></label>
        <input type="text" class="form-control" id="contact_first_name" name="contact_first_name"
            value="{{ old('contact_first_name', $data['contact_first_name'] ?? '') }}" required>
    </div>
    <div class="col-12 col-sm-6">
        <label for="contact_last_name" class="form-label">{{ __('Last Name(s)') }}<span class="ob-req">*</span></label>
        <input type="text" class="form-control" id="contact_last_name" name="contact_last_name"
            value="{{ old('contact_last_name', $data['contact_last_name'] ?? '') }}" required>
    </div>
    <div class="col-12 col-sm-6">
        <label for="contact_email" class="form-label">{{ __('Email Address') }}<span class="ob-req">*</span></label>
        <input type="email" class="form-control" id="contact_email" name="contact_email"
            value="{{ old('contact_email', $data['contact_email'] ?? '') }}" required>
    </div>
    <div class="col-12 col-sm-6">
        <label for="contact_email_confirmation" class="form-label">{{ __('Confirm Email Address') }}<span class="ob-req">*</span></label>
        <input type="email" class="form-control" id="contact_email_confirmation"
            name="contact_email_confirmation"
            value="{{ old('contact_email_confirmation') }}" required>
    </div>
    <div class="col-12 col-sm-6">
        <label for="contact_phone" class="form-label">{{ __('Phone Number') }}<span class="ob-req">*</span></label>
        <input type="tel" class="form-control input-mono" id="contact_phone" name="contact_phone"
            value="{{ old('contact_phone', $data['contact_phone'] ?? '') }}" required>
    </div>
    <div class="col-12 col-sm-6">
        <label for="contact_phone_confirmation" class="form-label">{{ __('Confirm Phone Number') }}<span class="ob-req">*</span></label>
        <input type="tel" class="form-control input-mono" id="contact_phone_confirmation"
            name="contact_phone_confirmation"
            value="{{ old('contact_phone_confirmation') }}" required>
    </div>
</div>