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

    // White/light logo used on the dark hero (start page). Path relative to
    // /public, e.g. 'img/hero/logo-white.svg'. Falls back to logo_path, then
    // to the text lockup baked into wizard/start.blade.php.
    'logo_hero_path' => env('BRANDING_LOGO_HERO_PATH', null),

    // YouTube video id for the "How to sign the document?" modal on the
    // success screen. Leave null to hide the button. e.g. 'dQw4w9WgXcQ'
    'help_video_id' => env('BRANDING_HELP_VIDEO_ID', null),

    // Wizard side-panel images, grouped by flow:
    //   public/img/side/fisica/{n}.png   (Persona Física)
    //   public/img/side/moral/{n}.png    (Persona Moral)
    // n is the step number; it wraps by the count below if there are more
    // steps than images. Use an int for one count across both groups, or an
    // array like ['fisica' => 7, 'moral' => 8] to set each independently.
    'side_image_count'   => 9,
    'side_image_default' => 'img/side/submit.png',

];
