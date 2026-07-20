{{--
| Individual · Step 3 — Identification & Documents
| ID type toggles the back-image field (INE only). Required docs below.
--}}
@php $idType = old('id_type', $data['id_type'] ?? 'ine'); $files = $files ?? []; @endphp

<fieldset class="mb-4">
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

<div class="row g-3 mb-2">
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





<hr class="my-4" style="border-color: var(--ob-border);">

<h2 class="h6 fw-bold mb-3">{{ __('Required Documents') }}</h2>
@include('partials.upload-field', [
'name' => 'proof_of_address', 'label' => __('Proof of Address'),
'exists' => isset($files['proof_of_address']),
'storedName' => $files['proof_of_address']['original_name'] ?? null,
])
@include('partials.upload-field', [
'name' => 'tax_certificate', 'label' => __('Tax Registration Certificate'),
'exists' => isset($files['tax_certificate']),
'storedName' => $files['tax_certificate']['original_name'] ?? null,
])

<hr style="border-color: var(--ob-border);">
<input type="hidden" name="credit_docs_step" value="1">
<div class="form-check mb-3">
    <input class="form-check-input" type="checkbox" value="1"
        id="credit_over_threshold" name="credit_over_threshold"
        data-ob-toggle="credit-docs"
        {{ old('credit_over_threshold', $data['credit_over_threshold'] ?? false) ? 'checked' : '' }}>
    <label class="form-check-label" for="credit_over_threshold">
        {{ __('Will you request credit over $300,000?') }}
    </label>
</div>

{{-- Revealed only when the checkbox is on. `hidden` is toggled by app.js. --}}
<div id="credit-docs" class="row g-3" data-ob-toggle-target="credit-docs"
    @unless(old('credit_over_threshold', $data['credit_over_threshold'] ?? false)) hidden @endunless>

    <p class="form-label mb-2">{{ __('Last 2 annual tax returns') }}<span class="ob-req">*</span></p>
    <div class="col-md-6">

        @include('partials.upload-field', [
        'name' => 'annual_return_1',
        'label' => "",
        'required' => false,
        'exists' => isset($files['annual_return_1']),
        'storedName' => $files['annual_return_1']['original_name'] ?? null,
        ])
    </div>
    <div class="col-md-6">
        @include('partials.upload-field', [
        'name' => 'annual_return_2',
        'label' => "",
        'required' => false,
        'exists' => isset($files['annual_return_2']),
        'storedName' => $files['annual_return_2']['original_name'] ?? null,
        ])
    </div>

    <p class="form-label mb-2 mt-3">
        {{ __('Partial financial statements with accountant signature and license') }}<span class="ob-req">*</span>
    </p>
    <div class="col-md-6">

        @include('partials.upload-field', [
        'name' => 'financials_partial_1',
        'label' => "",
        'required' => false,
        'exists' => isset($files['financials_partial_1']),
        'storedName' => $files['financials_partial_1']['original_name'] ?? null,
        ])
    </div>
    <div class="col-md-6">

        @include('partials.upload-field', [
        'name' => 'financials_partial_2',
        'label' => "",
        'required' => false,
        'exists' => isset($files['financials_partial_2']),
        'storedName' => $files['financials_partial_2']['original_name'] ?? null,
        ])
    </div>
</div>