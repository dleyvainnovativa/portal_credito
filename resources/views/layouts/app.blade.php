{{--
|--------------------------------------------------------------------------
| layouts/app.blade.php — Base layout
|--------------------------------------------------------------------------
| App shell with sticky footer. Holds the client logo in both the header
| and footer (configurable via config/branding.php). Loads Inter +
| JetBrains Mono, Font Awesome, and the Vite-bundled theme/app assets.
|
| Sections:
|   @section('title')   — page <title>
|   @yield('content')   — main page body
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0087FF">

    <title>@yield('title', config('branding.app_name', 'Onboarding'))</title>

    {{-- Preconnect + fonts: Inter (UI) and JetBrains Mono (monospace) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

    {{-- Font Awesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
        integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3w==" crossorigin="anonymous" referrerpolicy="no-referrer">

    {{-- Client-side annex limit + split messages (read by app.js / pdf-split) --}}
    <script>
        window.__obAnnexMaxBytes = {{(int) config('contisign.annex.max_size_kb', 15360) * 1024}};
        window.__obPartsLabel = @json(__('parts'));
        window.__obOversizeMsg = @json(__('The file is too large and a single page exceeds the :mb MB limit. Please upload a smaller file.', ['mb' => round(((int) config('contisign.annex.max_size_kb', 15360)) / 1024)]));
        window.__obSplitErrMsg = @json(__('The PDF could not be processed. Please try another file.'));
    </script>

    {{-- Vite: theme.css is imported through app.js --}}
    @vite(['resources/js/app.js', 'resources/css/theme.css'])

    @stack('head')
</head>

<body>
    <div class="ob-app">

        {{-- ===================== Header ===================== --}}
        <header class="ob-header">
            <div class="container-xxl d-flex align-items-center justify-content-between">
                @include('partials.logo', ['context' => 'header'])

                {{-- Optional right-side slot (e.g. language, help) --}}
                <div class="d-flex align-items-center gap-2">
                    @yield('header-actions')
                </div>
            </div>
        </header>

        {{-- ===================== Main: form + side image ===================== --}}
        @php
        // Resolve the side-image for this screen. Step views set the
        // 'side-image' section to their 1-indexed step number; other screens
        // fall back to the default. Images live in public/img/side/{n}.png and
        // wrap with modulo so any step count maps to an available image.
        $sideValue = trim($__env->yieldContent('side-image'));
        $sideCount = (int) config('branding.side_image_count', 7);
        $sideImg = config('branding.side_image_default', 'img/side/1.png');
        if ($sideValue !== '' && is_numeric($sideValue) && $sideCount > 0) {
        $n = (((int) $sideValue - 1) % $sideCount) + 1;
        $sideImg = "img/side/{$n}.png";
        }
        $hasSidePanel = file_exists(public_path('img/side/1.png'));
        @endphp

        <main class="ob-main">
            <div class="ob-split">
                {{-- Form column --}}
                <div class="ob-split__form py-4 py-md-5">
                    <div class="mx-auto px-3 px-md-4" style="max-width: var(--ob-content-max);">
                        @yield('content')
                    </div>
                </div>

                {{-- Sticky image panel (md+ only) --}}
                @if ($hasSidePanel)
                <aside class="ob-split__aside" style="--bg-image:  url({{ asset($sideImg) }});" aria-hidden="true">
                    <div class="ob-split__aside-sticky">
                        <img src="{{ asset($sideImg) }}" alt="" class="ob-split__img">
                    </div>
                </aside>
                @endif
            </div>
        </main>

        {{-- ===================== Footer ===================== --}}
        <!-- <footer class="ob-footer py-3">
            <div class="container-xxl d-flex flex-column flex-sm-row align-items-center justify-content-between gap-2">
                <span class="text-center text-sm-start">
                    &copy; {{ date('Y') }} {{ config('branding.company_name', 'Your Company') }}.
                    {{ __('All rights reserved.') }}
                </span>
                @include('partials.logo', ['context' => 'footer'])
            </div>
        </footer> -->

    </div>

    {{-- Toast container is created on demand by OB.toast(), no markup needed here --}}
    @stack('scripts')
</body>

</html>