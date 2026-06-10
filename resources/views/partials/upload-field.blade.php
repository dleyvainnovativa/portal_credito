{{--
| partials/upload-field.blade.php — Reusable document upload control
|
| Params:
|   name     — input name (also the document key)
|   label    — visible label
|   required — bool (default true)
|   exists   — bool: a file is already held by TempFileService (Phase 4)
|
| Renders a touch-friendly drop zone. A hidden "{name}_exists" field tells
| the Form Request that a previously uploaded file is present, so the user
| isn't forced to re-select it after navigating back.
--}}
@php
$required = $required ?? true;
$exists = $exists ?? false;
$storedName = $storedName ?? null;
$maxMb = round(((int) config('documents.max_size_kb', 81920)) / 1024, 1);
@endphp

<div class="mb-3">
    <label for="{{ $name }}" class="form-label">
        {{ $label }}@if($required)<span class="ob-req">*</span>@endif
    </label>

    <input type="hidden" name="{{ $name }}_exists" value="{{ $exists ? 1 : 0 }}">

    <label class="ob-upload {{ $exists ? 'is-filled' : '' }}" data-ob-upload="zone" for="{{ $name }}">
        <input type="file" id="{{ $name }}" name="{{ $name }}[]"
            class="visually-hidden" data-ob-upload="input"
            accept=".pdf,.jpg,.jpeg,.png">
        <span class="ob-upload__icon" aria-hidden="true">
            <i class="fa-solid {{ $exists ? 'fa-circle-check' : 'fa-cloud-arrow-up' }}"></i>
        </span>
        <span data-ob-upload="text">
            @if($exists)
            {{ $storedName ?: __('Already uploaded') }} — {{ __('Replace') }}
            @else
            {{ __('Drag a file here or click to select') }}
            @endif
        </span>
        <small style="color: var(--ob-text-subtle);">
            {{ __('PDF, JPG or PNG · max :size MB', ['size' => $maxMb]) }}
        </small>
    </label>
</div>