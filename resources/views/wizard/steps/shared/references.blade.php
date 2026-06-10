{{--
| Shared — Commercial References (used by both flows). Three references,
| each with company + phone. Array input: references[i][company|phone].
--}}
@php $refs = old('references', $data['references'] ?? []); @endphp

@for ($i = 0; $i < 3; $i++)
    <div class="mb-3 pb-3 @if($i < 2) border-bottom @endif" style="border-color: var(--ob-border) !important;">
    <h2 class="h6 fw-bold mb-3">{{ __('Reference :n', ['n' => $i + 1]) }}</h2>
    <div class="row g-3">
        <div class="col-12 col-sm-7">
            <label for="ref_company_{{ $i }}" class="form-label">{{ __('Company Name') }}<span class="ob-req">*</span></label>
            <input type="text" class="form-control" id="ref_company_{{ $i }}"
                name="references[{{ $i }}][company]"
                value="{{ $refs[$i]['company'] ?? '' }}" required>
        </div>
        <div class="col-12 col-sm-5">
            <label for="ref_phone_{{ $i }}" class="form-label">{{ __('Phone Number') }}<span class="ob-req">*</span></label>
            <input type="tel" class="form-control input-mono" id="ref_phone_{{ $i }}"
                name="references[{{ $i }}][phone]"
                value="{{ $refs[$i]['phone'] ?? '' }}" required>
        </div>
    </div>
    </div>
    @endfor