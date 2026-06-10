{{--
| Company (Persona Moral) · Primary Contact
| Reduced set: ejecutivo email, contact name, email + phone (NO confirmations).
--}}
<div class="mb-3">
    <label for="sales_rep_email" class="form-label">{{ __('Your Executive\'s Email') }}<span class="ob-req">*</span></label>
    <input type="email" class="form-control" id="sales_rep_email" name="sales_rep_email"
        value="{{ old('sales_rep_email', $data['sales_rep_email'] ?? '') }}" required>
</div>

<hr class="my-4">

<div class="row g-3">
    <div class="col-12 col-sm-6">
        <label for="contact_first_name" class="form-label">Su {{ __('First Name(s)') }}<span class="ob-req">*</span></label>
        <input type="text" class="form-control" id="contact_first_name" name="contact_first_name"
            value="{{ old('contact_first_name', $data['contact_first_name'] ?? '') }}" required>
    </div>
    <div class="col-12 col-sm-6">
        <label for="contact_last_name" class="form-label">Su {{ __('Last Name(s)') }}<span class="ob-req">*</span></label>
        <input type="text" class="form-control" id="contact_last_name" name="contact_last_name"
            value="{{ old('contact_last_name', $data['contact_last_name'] ?? '') }}" required>
    </div>
    <div class="col-12 col-sm-6">
        <label for="contact_email" class="form-label">Su {{ __('Email Address') }}<span class="ob-req">*</span></label>
        <input type="email" class="form-control" id="contact_email" name="contact_email"
            value="{{ old('contact_email', $data['contact_email'] ?? '') }}" required>
    </div>
    <div class="col-12 col-sm-6">
        <label for="contact_phone" class="form-label">Su {{ __('Phone Number') }}<span class="ob-req">*</span></label>
        <input type="tel" class="form-control input-mono" id="contact_phone" name="contact_phone"
            value="{{ old('contact_phone', $data['contact_phone'] ?? '') }}" required>
    </div>
</div>