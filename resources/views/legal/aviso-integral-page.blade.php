<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Privacy Notice') }} · {{ config('branding.app_name', 'Sistemas Contino') }}</title>
    @vite(['resources/css/theme.css'])
</head>

<body style="background: var(--ob-bg); padding: 2rem 1rem;">
    <main class="mx-auto" style="max-width: 820px;">
        <div class="text-start mb-5">
            <img src='{{asset("img/logo.png")}}' height="60" alt="Logo" srcset="">
        </div>
        <div class="ob-card">
            <div class="ob-card__body ob-legal ob-legal--page">
                @include('partials.legal.aviso-integral')
            </div>
        </div>
    </main>
</body>

</html>