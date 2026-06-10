{{--
| Company · Step 4 — Legal Representative
| Rep details + email confirmation + DOB + conditional identification.
--}}
@php $idType = old('id_type', $data['id_type'] ?? 'ine'); $files = $files ?? []; @endphp

<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6">
        <label for="rep_first_name" class="form-label">{{ __('First Name(s)') }}<span class="ob-req">*</span></label>
        <input type="text" class="form-control" id="rep_first_name" name="rep_first_name"
            value="{{ old('rep_first_name', $data['rep_first_name'] ?? '') }}" required>
    </div>
    <div class="col-12 col-sm-6">
        <label for="rep_last_name" class="form-label">{{ __('Last Name(s)') }}<span class="ob-req">*</span></label>
        <input type="text" class="form-control" id="rep_last_name" name="rep_last_name"
            value="{{ old('rep_last_name', $data['rep_last_name'] ?? '') }}" required>
    </div>
    <div class="col-12 col-sm-6">
        <label for="rep_email" class="form-label">{{ __('Email Address') }}<span class="ob-req">*</span></label>
        <input type="email" class="form-control" id="rep_email" name="rep_email"
            value="{{ old('rep_email', $data['rep_email'] ?? '') }}" required>
    </div>
    <div class="col-12 col-sm-6">
        <label for="rep_email_confirmation" class="form-label">{{ __('Confirm Email Address') }}<span class="ob-req">*</span></label>
        <input type="email" class="form-control" id="rep_email_confirmation" name="rep_email_confirmation"
            value="{{ old('rep_email_confirmation') }}" required>
    </div>
    <div class="col-12 col-sm-6">
        <label for="rep_phone" class="form-label">{{ __('Phone Number') }}<span class="ob-req">*</span></label>
        <input type="tel" class="form-control input-mono" id="rep_phone" name="rep_phone"
            value="{{ old('rep_phone', $data['rep_phone'] ?? '') }}" required>
    </div>
    <div class="col-12 col-sm-6">
        <label for="rep_phone_confirmation" class="form-label">{{ __('Confirm Phone Number') }}<span class="ob-req">*</span></label>
        <input type="tel" class="form-control input-mono" id="rep_phone_confirmation"
            name="rep_phone_confirmation" value="{{ old('rep_phone_confirmation') }}" required>
    </div>
    <div class="col-12 col-sm-6">
        <label for="rep_dob" class="form-label">{{ __('Date of Birth') }}<span class="ob-req">*</span></label>
        <input type="date" class="form-control" id="rep_dob" name="rep_dob"
            value="{{ old('rep_dob', $data['rep_dob'] ?? '') }}" required>
    </div>
</div>

<fieldset class="mb-3">
    <legend class="form-label d-block mb-2">{{ __('Identification Type') }}<span class="ob-req">*</span></legend>
    <div class="btn-group flex-wrap" role="group" aria-label="{{ __('Identification Type') }}">
        @foreach (['ine' => __('INE'), 'passport' => __('Passport'), 'immigration' => __('Immigration Document')] as $val => $text)
        <input type="radio" class="btn-check" name="id_type" id="id_type_{{ $val }}"
            value="{{ $val }}" autocomplete="off" data-ob-idtype
            {{ $idType === $val ? 'checked' : '' }} required>
        <label class="btn btn-outline-secondary" for="id_type_{{ $val }}">{{ $text }}</label>
        @endforeach
    </div>
</fieldset>

<div class="row g-3">
    <div class="col-12 col-sm-6" data-ob-idfield="front">
        @include('partials.upload-field', [
        'name' => 'id_front', 'label' => __('Front Image'),
        'exists' => isset($files['id_front']),
        'storedName' => $files['id_front']['original_name'] ?? null,
        ])
    </div>
    <div class="col-12 col-sm-6" data-ob-idfield="back" @if($idType !=='ine' ) style="display:none" @endif>
        @include('partials.upload-field', [
        'name' => 'id_back', 'label' => __('Back Image'),
        'exists' => isset($files['id_back']),
        'storedName' => $files['id_back']['original_name'] ?? null,
        ])
    </div>
</div>