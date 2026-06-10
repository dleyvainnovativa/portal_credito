{{--
| Company · Step 1 — Business Information
--}}
<div class="row g-3">
    <div class="col-12 col-sm-6">
        <label for="rfc" class="form-label">RFC de la Empresa<span class="ob-req">*</span></label>
        <input type="text" class="form-control input-mono text-uppercase" id="rfc" name="rfc"
            maxlength="12" value="{{ old('rfc', $data['rfc'] ?? '') }}" autocomplete="off" required>
        <div class="form-text">{{ __('13 characters for individuals.') }} (12 — Persona Moral)</div>
    </div>
    <div class="col-12 col-sm-6">
        <label for="website" class="form-label">{{ __('Website') }}</label>
        <input type="url" class="form-control" disabled id="website" name="website"
            placeholder="https://" value="{{ old('website', $data['website'] ?? '') }}">
    </div>
    <div class="col-12">
        <label for="legal_name" class="form-label">{{ __('Legal Business Name') }}<span class="ob-req">*</span></label>
        <input type="text" class="form-control" id="legal_name" name="legal_name"
            value="{{ old('legal_name', $data['legal_name'] ?? '') }}" required>
    </div>
    <div class="col-12">
        <label for="commercial_name" class="form-label">{{ __('Commercial Name') }}<span class="ob-req">*</span></label>
        <input type="text" class="form-control" id="commercial_name" name="commercial_name"
            value="{{ old('commercial_name', $data['commercial_name'] ?? '') }}" required>
    </div>
</div>