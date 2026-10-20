<?php

/*
|--------------------------------------------------------------------------
| Privacy / Aviso de Privacidad configuration
|--------------------------------------------------------------------------
| Centralizes the privacy-notice version and public links. The `version` is
| stored with each acceptance as legal evidence (the Aviso Integral §6 /
| LFPDPPP requires keeping the accepted version plus date and time).
|
| When the notices are updated, bump `version` here and update the two
| partials (resources/views/partials/legal/aviso-*.blade.php).
*/

return [

    // Version identifier recorded with each acceptance (the notices' own
    // "Fecha de última actualización").
    'version' => env('PRIVACY_AVISO_VERSION', '2026-10-15'),

    // Public page referenced inside the notices. Used for display/reference;
    // the portal also serves the integral in-app at route('legal.aviso-integral').
    'public_url' => env('PRIVACY_PUBLIC_URL', 'https://www.sistemascontino.com.mx/solicitud_credito/'),

];
