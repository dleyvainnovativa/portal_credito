{{--
| Individual · Step 1 — Personal Information
| Fields: RFC, First Name(s), Last Name(s), Website
| $data holds previously captured values for this step (resume / back-nav).
--}}
<div class="row g-3">
    <div class="col-12 col-sm-6">
        <label for="rfc" class="form-label">RFC<span class="ob-req">*</span></label>
        <input type="text" class="form-control input-mono text-uppercase" id="rfc" name="rfc"
            maxlength="13" value="{{ old('rfc', $data['rfc'] ?? '') }}"
            autocomplete="off" required>
        <div class="form-text">{{ __('13 characters for individuals.') }}</div>
    </div>

    <div class="col-12 col-sm-6">
        <label for="website" class="form-label">{{ __('Website') }}</label>
        <input type="url" class="form-control" disabled id="website" name="website"
            placeholder="https://" value="{{ old('website', $data['website'] ?? '') }}">
    </div>

    <div class="col-12 col-sm-6">
        <label for="first_name" class="form-label">{{ __('First Name(s)') }}<span class="ob-req">*</span></label>
        <input type="text" class="form-control" id="first_name" name="first_name"
            value="{{ old('first_name', $data['first_name'] ?? '') }}"
            autocomplete="given-name" required>
    </div>

    <div class="col-12 col-sm-6">
        <label for="last_name" class="form-label">{{ __('Last Name(s)') }}<span class="ob-req">*</span></label>
        <input type="text" class="form-control" id="last_name" name="last_name"
            value="{{ old('last_name', $data['last_name'] ?? '') }}"
            autocomplete="family-name" required>
    </div>
</div>