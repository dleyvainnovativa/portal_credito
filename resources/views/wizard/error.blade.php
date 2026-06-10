{{-- wizard/error.blade.php — shown when submission to Contisign fails. --}}
@extends('layouts.app')

@section('title', __('Submission failed'))

@section('content')
<div class="ob-card ob-reveal text-center">
    <div class="ob-card__body py-5">
        <span class="d-inline-flex align-items-center justify-content-center mb-4"
            style="width:72px;height:72px;border-radius:var(--ob-radius-pill);
                         background:rgba(229,72,77,.1);color:var(--ob-danger);">
            <i class="fa-solid fa-triangle-exclamation fa-2x" aria-hidden="true"></i>
        </span>
        <h1 class="h4 fw-bold mb-2">{{ __('Submission failed') }}</h1>
        <p class="mb-4" style="color: var(--ob-text-muted); max-width: 480px; margin-inline: auto;">
            {{ $message ?? __('Something went wrong while submitting your application.') }}
        </p>
        <a href="{{ url()->previous() }}" class="btn btn-primary">
            <i class="fa-solid fa-rotate-left me-1" aria-hidden="true"></i>{{ __('Try again') }}
        </a>
    </div>
</div>
@endsection