{{--
|--------------------------------------------------------------------------
| wizard/start.blade.php — Hero / applicant-type decision page
|--------------------------------------------------------------------------
| Full-bleed dark hero (Sistemas Contino "Solicitud de Crédito" landing).
| Two CTAs post to wizard.begin with applicant_type = individual | company,
| so no controller change is needed. Standalone layout: it deliberately
| does NOT extend layouts.app so the light split-shell / header don't wrap
| the dark hero. The wizard step screens keep the light theme.
|
| PLACEHOLDER ASSETS — swap the paths marked "SWAP:" for the real files:
|   - Background image  -> public/img/hero/hero-bg.jpg
|   - Laptop + decoration (transparent PNG) -> public/img/hero/hero-laptop.png
|   - Client logo (white/horizontal) -> config('branding.logo_hero_path')
|   - ContiSign "powered by" mark -> public/img/hero/contisign.svg
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0a1b3d">

    <title>@yield('title', __('Solicitud de Crédito')) · {{ config('branding.app_name', 'Sistemas Contino') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Condensed:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    @vite(['resources/js/app.js', 'resources/css/theme.css'])

    <style>
        /* ==================================================================
           Hero - scoped to .hero so it never leaks into the wizard steps
           ================================================================== */
        .hero {
            /* SWAP: real background image path */
            --hero-bg: url("{{ asset('img/hero/hero-bg2.png') }}");

            position: relative;
            min-height: 100svh;
            min-height: 100vh;
            width: 100%;
            overflow: hidden;
            color: #fff;
            font-family: var(--ob-font-ui, 'Roboto', system-ui, sans-serif);
            background-color: #061431;
            /* fallback if image missing */
            background-image: var(--hero-bg);
            background-size: cover;
            background-position: center right;
            background-repeat: no-repeat;
            display: flex;
            flex-direction: column;
        }

        /* Readability veil over the background (stronger on the left) */
        .hero::before {
            content: "";
            position: absolute;
            inset: 0;
            background:
                linear-gradient(90deg, rgba(4, 13, 33, 0.92) 0%, rgba(4, 13, 33, 0.72) 38%, rgba(4, 13, 33, 0.25) 70%, rgba(4, 13, 33, 0.15) 100%),
                radial-gradient(120% 80% at 100% 40%, rgba(0, 135, 255, 0.10), transparent 60%);
            z-index: 0;
        }

        .hero__inner {
            position: relative;
            z-index: 1;
            flex: 1;
            width: 100%;
            max-width: 1740px;
            margin: 0 auto;
            padding: clamp(1.5rem, 4vw, 3.5rem) clamp(1.25rem, 5vw, 5rem);
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1.05fr);
            align-items: center;
            gap: 2rem;
        }

        /* ------------------------------- Copy ------------------------------ */
        .hero__copy {
            animation: heroFade 620ms var(--ob-ease, ease) both;
        }

        @keyframes heroFade {
            from {
                opacity: 0;
                transform: translateY(14px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .hero__copy {
                animation: none;
            }
        }

        .hero__logo {
            height: 54px;
            width: auto;
            margin-bottom: clamp(2rem, 6vh, 4rem);
        }

        .hero__logo-fallback {
            display: inline-flex;
            flex-direction: column;
            font-weight: 800;
            letter-spacing: .5px;
            font-size: 1.5rem;
            margin-bottom: clamp(2rem, 6vh, 4rem);
            color: #fff;
        }

        .hero__logo-fallback small {
            display: block;
            font-size: .55rem;
            letter-spacing: 2px;
            font-weight: 600;
            color: rgba(255, 255, 255, .6);
            margin-top: .25rem;
        }

        .hero__eyebrow {
            font-size: clamp(.75rem, 1vw, .9rem);
            font-weight: 700;
            letter-spacing: .38em;
            text-transform: uppercase;
            color: #4da3ff;
            margin-bottom: 1rem;
        }

        .hero__title {
            font-weight: 800;
            line-height: 1.02;
            letter-spacing: -.02em;
            font-size: clamp(2.75rem, 6vw, 5.5rem);
            margin: 0 0 1.5rem;
        }

        .hero__title span {
            color: #0f8bff;
            display: block;
        }

        .hero__subtitle {
            font-size: clamp(1rem, 1.4vw, 1.35rem);
            line-height: 1.5;
            color: rgba(255, 255, 255, .82);
            max-width: 34ch;
            margin: 0 0 clamp(2rem, 4vh, 2.75rem);
        }

        /* ------------------------------- CTAs ------------------------------ */
        .hero__actions {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: clamp(2rem, 5vh, 3rem);
        }

        .hero__actions form {
            margin: 0;
        }

        .hero-btn {
            appearance: none;
            border: 1.5px solid transparent;
            border-radius: 10px;
            padding: 1.05rem 2.25rem;
            font-size: 1.05rem;
            font-weight: 700;
            letter-spacing: .02em;
            text-transform: uppercase;
            cursor: pointer;
            transition: transform .18s var(--ob-ease, ease), box-shadow .18s ease, background .18s ease, color .18s ease;
            width: 100%;
        }

        .hero-btn--primary {
            background: #0f8bff;
            color: #fff;
            box-shadow: 0 6px 22px rgba(15, 139, 255, .35);
        }

        .hero-btn--primary:hover {
            background: #2f9dff;
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(15, 139, 255, .5);
        }

        .hero-btn--ghost {
            background: transparent;
            color: #fff;
            border-color: rgba(255, 255, 255, .5);
        }

        .hero-btn--ghost:hover {
            background: rgba(255, 255, 255, .1);
            border-color: #fff;
            transform: translateY(-2px);
        }

        /* --------------------------- Trust row ----------------------------- */
        .hero__trust {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: .65rem 1.5rem;
            font-size: .92rem;
            color: rgba(255, 255, 255, .8);
        }

        .hero__trust span {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
        }

        .hero__trust i {
            color: #4da3ff;
        }

        .hero__trust .dot {
            width: 4px;
            height: 4px;
            border-radius: 50%;
            background: rgba(255, 255, 255, .35);
        }

        /* ----------------------------- Laptop ------------------------------ */
        .hero__art {
            position: relative;
            justify-self: end;
            width: 100%;
        }

        .hero__art img {
            display: block;
            width: 100%;
            height: auto;
            max-width: 780px;
            margin-left: auto;
            filter: drop-shadow(0 40px 80px rgba(0, 0, 0, .55));
        }

        /* ------------------------- Powered by ------------------------------ */
        .hero__powered {
            position: absolute;
            right: clamp(1.25rem, 5vw, 5rem);
            bottom: clamp(1rem, 3vh, 2rem);
            z-index: 2;
            display: flex;
            align-items: center;
            gap: .5rem;
            font-size: .8rem;
            font-style: italic;
            color: rgba(255, 255, 255, .7);
        }

        .hero__powered img {
            height: 56px;
            width: auto;
        }

        /* ==================================================================
           Responsive - hide the laptop entirely on mobile/tablet
           ================================================================== */
        @media (max-width: 991.98px) {
            .hero {
                background-position: center;
            }

            .hero__inner {
                grid-template-columns: 1fr;
                text-align: left;
                align-content: center;
            }

            .hero__art,
            .hero__powered {
                display: none;
                /* laptop hidden on mobile per spec */
            }

            .hero__subtitle {
                max-width: 46ch;
            }
        }

        @media (max-width: 575.98px) {
            .hero__actions {
                flex-direction: column;
            }

            .hero__actions form {
                width: 100%;
            }

            .hero__trust {
                gap: .6rem 1rem;
                font-size: .85rem;
            }

            .hero__trust .dot {
                display: none;
            }

            .hero__trust span {
                flex: 0 0 100%;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .hero-btn {
                transition: none;
            }
        }
    </style>
</head>

<body>
    <main class="hero">
        <div class="hero__inner">

            {{-- ===================== Copy column ===================== --}}
            <div class="hero__copy">
                @php
                $logoPath = config('branding.logo_hero_path', config('branding.logo_path'));
                $logoExists = $logoPath && file_exists(public_path($logoPath));
                @endphp

                @if ($logoExists)
                <img src="{{ asset($logoPath) }}" alt="{{ config('branding.company_name', 'Sistemas Contino') }}" class="hero__logo">
                @else
                {{-- SWAP: set config('branding.logo_hero_path') to a white logo --}}
                <span class="hero__logo-fallback">
                    <span>SISTEMAS<span style="color:#0f8bff;">CONTINO</span></span>
                    <small>SOLUCIONES DE GESTIÓN DEL DOCUMENTO</small>
                </span>
                @endif

                <p class="hero__eyebrow">{{ __('Plataforma de Financiamiento') }}</p>

                <h1 class="hero__title">
                    {{ __('Solicitud de') }}
                    <span>{{ __('Crédito') }}</span>
                </h1>

                <p class="hero__subtitle">
                    {{ __('Adquiere la tecnología que tu negocio necesita, con un proceso 100% digital y firma electrónica.') }}
                </p>

                <div class="hero__actions">
                    <form method="POST" action="{{ route('wizard.begin') }}">
                        @csrf
                        <input type="hidden" name="applicant_type" value="individual">
                        <button type="submit" class="hero-btn hero-btn--primary">
                            {{ __('Soy Persona Física') }}
                        </button>
                    </form>

                    <form method="POST" action="{{ route('wizard.begin') }}">
                        @csrf
                        <input type="hidden" name="applicant_type" value="company">
                        <button type="submit" class="hero-btn hero-btn--ghost">
                            {{ __('Soy una Empresa') }}
                        </button>
                    </form>
                </div>

                <div class="hero__trust">
                    <img width="100%" src="{{ asset('img/hero/actions.svg') }}" alt="">

                    <!-- <span><i class="fa-solid fa-pen-nib" aria-hidden="true"></i> {{ __('Firma electrónica ContiSign') }}</span>
                    <span class="dot" aria-hidden="true"></span>
                    <span><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> {{ __('Datos protegidos') }}</span>
                    <span class="dot" aria-hidden="true"></span>
                    <span><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i> {{ __('Respuesta en 24h') }}</span> -->
                </div>
            </div>

            {{-- ===================== Laptop art (desktop only) ===================== --}}
            <div class="hero__art" aria-hidden="true">
                {{-- SWAP: real laptop + decoration PNG (transparent) --}}
                <!-- <img src="{{ asset('img/hero/hero-laptop.png') }}" alt=""> -->
            </div>
        </div>

        {{-- ===================== Powered by (desktop only) ===================== --}}
        <div class="hero__powered" aria-hidden="true">
            <img src="{{ asset('img/hero/contisign.png') }}" alt="ContiSign">
        </div>
    </main>
</body>

</html>