{{-- Individual · Review & Submit — full normalized summary + final confirm. --}}
@include('wizard.steps.shared.review-summary', [
'payload' => $payload ?? null,
'flow' => $flow,
'docLabels' => array_merge(
['id_front' => 'Front Image', 'id_back' => 'Back Image'],
config('documents.required.individual', [])
),
])
@include('wizard.steps.shared.confirm-submit')