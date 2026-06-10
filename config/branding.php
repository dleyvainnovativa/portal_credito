<?php

/*
|--------------------------------------------------------------------------
| Branding configuration
|--------------------------------------------------------------------------
| Centralizes display names and the client logo so they can be swapped
| without touching Blade templates. Drop the client's horizontal logo into
| /public and point BRANDING_LOGO_PATH at it (relative to /public).
*/

return [

    'app_name'     => env('BRANDING_APP_NAME', 'Client Onboarding'),
    'company_name' => env('BRANDING_COMPANY_NAME', 'Your Company'),

    // Path relative to /public, e.g. 'img/client-logo.svg'. Leave null to
    // show the dashed placeholder until the client provides their logo.
    'logo_path'    => env('BRANDING_LOGO_PATH', null),
    'logo_alt'     => env('BRANDING_LOGO_ALT', 'Client logo'),

    // YouTube video id for the "How to sign the document?" modal on the
    // success screen. Leave null to hide the button. e.g. 'dQw4w9WgXcQ'
    'help_video_id' => env('BRANDING_HELP_VIDEO_ID', null),

];
