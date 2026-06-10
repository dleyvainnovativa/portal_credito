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