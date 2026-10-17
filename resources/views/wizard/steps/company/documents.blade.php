{{--
| Company · Step 3 — Corporate Documents
| Renders an upload field per required document from config/documents.php.
--}}
@php $files = $files ?? []; @endphp
<h2 class="h6 fw-bold mb-3">{{ __('Required Documents') }}</h2>

@foreach (config('documents.required.company', []) as $key => $label)
@include('partials.upload-field', [
'name' => $key,
'label' => __($label),
'exists' => isset($files[$key]),
'storedName' => $files[$key]['original_name'] ?? null,
])
@endforeach


@php $overThreshold = (bool) old('credit_over_threshold', $data['credit_over_threshold'] ?? false); @endphp
<input type="hidden" name="credit_docs_step" value="1">
{{-- The credit-line range is chosen up front in the intake sheet, so the old
     ">$300,000" checkbox is gone. This hidden input carries the derived flag so
     validation and the conditional-doc reveal stay driven by the chosen tier. --}}
<input type="hidden" name="credit_over_threshold" value="{{ $overThreshold ? 1 : 0 }}">

@if ($overThreshold)
<hr style="border-color: var(--ob-border);">
<div id="credit-docs" class="row g-3">

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
@endif