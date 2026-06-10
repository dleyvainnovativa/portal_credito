{{--
|--------------------------------------------------------------------------
| partials/stepper.blade.php — Responsive wizard progress indicator
|--------------------------------------------------------------------------
| Desktop (>=md): horizontal numbered stepper with connecting track.
| Mobile  (<md):  "Step X of Y" label + current step name + thin bar.
|
| Usage:
|   @include('partials.stepper', [
|       'steps'   => ['Personal Info', 'Address', 'ID & Documents', ...],
|       'current' => 2,   // 1-indexed
|   ])
--}}
@php
$steps = $steps ?? [];
$current = $current ?? 1;
$total = count($steps);
$pct = $total > 1 ? round((($current - 1) / ($total - 1)) * 100) : 100;
$currentLabel = $steps[$current - 1] ?? '';
@endphp

@if ($total > 0)
{{-- Desktop --}}
<ol class="ob-stepper mb-4" aria-label="{{ __('Progress') }}">
    @foreach ($steps as $i => $label)
    @php $n = $i + 1; @endphp
    <li class="ob-stepper__item @if($n < $current) is-complete @elseif($n === $current) is-active @endif"
        @if($n===$current) aria-current="step" @endif>
        <span class="ob-stepper__dot" aria-hidden="true"></span>
        <span class="ob-stepper__label">{{ $label }}</span>
    </li>
    @endforeach
</ol>

{{-- Mobile --}}
<div class="ob-stepper-mobile mb-4">
    <div class="ob-stepper-mobile__meta">
        <span class="ob-stepper-mobile__current">{{ $currentLabel }}</span>
        <span class="ob-stepper-mobile__count">{{ $current }} / {{ $total }}</span>
    </div>
    <div class="ob-stepper-mobile__track" role="progressbar"
        aria-valuenow="{{ $current }}" aria-valuemin="1" aria-valuemax="{{ $total }}"
        aria-label="{{ __('Step :n of :t', ['n' => $current, 't' => $total]) }}">
        <div class="ob-stepper-mobile__fill" style="width: {{ $pct }}%;"></div>
    </div>
</div>
@endif