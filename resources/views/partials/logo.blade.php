{{--
|--------------------------------------------------------------------------
| partials/logo.blade.php — Client logo slot
|--------------------------------------------------------------------------
| Renders the client's horizontal logo in either the header or footer.
| Source is configurable via config/branding.php (no code change needed
| to swap logos). Falls back to a labelled placeholder when unset so the
| layout never breaks during development.
|
| Usage: @include('partials.logo', ['context' => 'header'|'footer'])
--}}
@php
$context = $context ?? 'header';
$logoPath = config('branding.logo_path'); // e.g. 'img/client-logo.svg' in /public
$logoExists = $logoPath && file_exists(public_path($logoPath));
$alt = config('branding.logo_alt', config('branding.app_name', 'Client logo'));
@endphp

@if ($context === 'header')
<a href="{{ url('/') }}" class="ob-logo" aria-label="{{ $alt }}">
    @if ($logoExists)
    <img src="{{ asset($logoPath) }}" alt="{{ $alt }}" class="ob-logo__img">
    @else
    <span class="ob-logo__placeholder">
        <i class="fa-regular fa-image" aria-hidden="true"></i>
        {{ __('Client logo') }}
    </span>
    @endif
</a>
@else
@if ($logoExists)
<img src="{{ asset($logoPath) }}" alt="{{ $alt }}" class="ob-footer__logo">
@endif
@endif