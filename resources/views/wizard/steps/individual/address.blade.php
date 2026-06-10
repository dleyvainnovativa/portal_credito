{{--
| Address — shared by Individual (Step 2) and Company (Step 2 / Business Address)
| Fields: Street and Number, Postal Code, Colonia, City, State, Country.
|
| Postal-code lookup: on entering a 5-digit code, app.js will call the
| Phase 3 endpoint to auto-fill State + City and populate the Colonia select.
| The data-ob-postal hooks below are the integration points.
--}}
<div class="row g-3">
    <div class="col-12">
        <label for="street" class="form-label">{{ __('Street and Number') }}<span class="ob-req">*</span></label>
        <input type="text" class="form-control" id="street" name="street"
            value="{{ old('street', $data['street'] ?? '') }}" autocomplete="address-line1" required>
    </div>

    <div class="col-12 col-sm-4">
        <label for="postal_code" class="form-label">{{ __('Postal Code') }}<span class="ob-req">*</span></label>
        <input type="text" class="form-control input-mono" id="postal_code" name="postal_code"
            inputmode="numeric" maxlength="5" pattern="\d{5}"
            value="{{ old('postal_code', $data['postal_code'] ?? '') }}"
            data-ob-postal="input" autocomplete="postal-code" required>
        <div class="form-text" data-ob-postal="status"></div>
    </div>

    <div class="col-12 col-sm-8">
        <label for="colonia" class="form-label">{{ __('Neighborhood (Colonia)') }}<span class="ob-req">*</span></label>
        <select class="form-select" id="colonia" name="colonia" data-ob-postal="colonia"
            data-selected="{{ old('colonia', $data['colonia'] ?? '') }}" required>
            <option value="">{{ __('Enter postal code first') }}</option>
        </select>
        {{-- Manual fallback: shown when a postal code isn't in the catalog. --}}
        <input type="text" class="form-control mt-2 d-none" id="colonia_manual"
            data-ob-postal="colonia-manual" placeholder="{{ __('Neighborhood (Colonia)') }}"
            value="{{ old('colonia', $data['colonia'] ?? '') }}">
    </div>

    <div class="col-12 col-sm-6">
        <label for="city" class="form-label">{{ __('City') }}<span class="ob-req">*</span></label>
        <input type="text" class="form-control" id="city" name="city"
            value="{{ old('city', $data['city'] ?? '') }}" data-ob-postal="city"
            readonly required>
    </div>

    <div class="col-12 col-sm-6">
        <label for="state" class="form-label">{{ __('State') }}<span class="ob-req">*</span></label>
        <input type="text" class="form-control" id="state" name="state"
            value="{{ old('state', $data['state'] ?? '') }}" data-ob-postal="state"
            readonly required>
    </div>

    <div class="col-12">
        <label for="country" class="form-label">{{ __('Country') }}</label>
        <input type="text" class="form-control" id="country" name="country"
            value="{{ old('country', $data['country'] ?? 'Mexico') }}" readonly>
    </div>
</div>