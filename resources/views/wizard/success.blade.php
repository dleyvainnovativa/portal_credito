{{-- wizard/success.blade.php — shown after a successful Contisign submission. --}}
@extends('layouts.app')

@section('title', __('Application submitted'))

@section('content')
<div class="ob-card ob-reveal text-center">
    <div class="ob-card__body py-5">
        <span class="d-inline-flex align-items-center justify-content-center mb-4"
            style="width:72px;height:72px;border-radius:var(--ob-radius-pill);
                         background:rgba(31,157,87,.1);color:var(--ob-success);">
            <i class="fa-solid fa-check fa-2x" aria-hidden="true"></i>
        </span>
        <h1 class="h4 fw-bold mb-2">{{ __('Application submitted') }}</h1>
        <p class="mb-4" style="color: var(--ob-text-muted); max-width: 480px; margin-inline: auto;">
            {{ __('Your onboarding information has been sent for electronic signature processing. You may now close this window.') }}
        </p>
        <div class="d-flex flex-column flex-sm-row gap-2 justify-content-center">
            <a href="{{ route('wizard.start') }}" class="btn btn-outline-secondary">
                {{ __('Start a new application') }}
            </a>
            @if (config('branding.help_video_id'))
            <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                data-bs-target="#howToSignModal">
                <i class="fa-brands fa-youtube me-1" aria-hidden="true"></i>{{ __('How to sign the document?') }}
            </button>
            @endif
        </div>
    </div>
</div>

@if (config('branding.help_video_id'))
{{-- YouTube help modal. The iframe src is only set when the modal opens
             (and cleared on close) so the video doesn't load/play in the
             background before the user asks for it. --}}
<div class="modal fade" id="howToSignModal" tabindex="-1"
    aria-labelledby="howToSignLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius: var(--ob-radius-lg); overflow: hidden;">
            <div class="modal-header">
                <h2 class="h6 fw-bold mb-0" id="howToSignLabel">
                    {{ __('How to sign the document?') }}
                </h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal"
                    aria-label="{{ __('Close') }}"></button>
            </div>
            <div class="modal-body p-0">
                <div class="ratio ratio-16x9">
                    <iframe id="howToSignFrame" src="" title="{{ __('How to sign the document?') }}"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen></iframe>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script type="module">
    const modal = document.getElementById('howToSignModal');
    if (modal) {
        const frame = document.getElementById('howToSignFrame');
        const videoId = @json(config('branding.help_video_id'));
        const src = `https://www.youtube.com/embed/${videoId}?rel=0`;
        modal.addEventListener('shown.bs.modal', () => {
            frame.src = src;
        });
        modal.addEventListener('hidden.bs.modal', () => {
            frame.src = '';
        });
    }
</script>
@endpush