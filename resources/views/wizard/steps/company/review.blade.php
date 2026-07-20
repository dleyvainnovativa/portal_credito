{{-- Company · Review & Submit — full normalized summary + final confirm. --}}
@include('wizard.steps.shared.review-summary', [
'payload' => $payload ?? null,
'flow' => $flow,
'docLabels' => array_merge(
config('documents.required.company', []),
config('documents.credit_over_threshold', []),
['id_front' => 'Front Image', 'id_back' => 'Back Image']
),
])
@include('wizard.steps.shared.confirm-submit')