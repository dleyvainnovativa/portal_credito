<?php

use App\Http\Controllers\Api\PostalCodeController;
use App\Http\Controllers\WizardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web routes — Onboarding wizard
|--------------------------------------------------------------------------
| One-way onboarding flow. No auth gate on the public wizard (see the open
| auth question in SETUP.md). Step keys are validated by the controller
| against the active flow definition.
*/

// Entry: applicant-type selector
Route::get('/', [WizardController::class, 'start'])->name('wizard.start');
Route::post('/onboarding/begin', [WizardController::class, 'begin'])->name('wizard.begin');

// Postal code lookup (read-only JSON, consumed by the address step).
Route::get('/postal/{code}', [PostalCodeController::class, 'show'])
    ->where('code', '\d{5}')
    ->name('postal.lookup');

// Wizard steps. {step} is a flow step key (e.g. personal, address, review).
Route::prefix('onboarding')->group(function () {
    Route::get('/step/{step}',  [WizardController::class, 'show'])->name('wizard.step');
    Route::post('/step/{step}', [WizardController::class, 'next'])->name('wizard.next');
    Route::get('/back/{step}',  [WizardController::class, 'back'])->name('wizard.back');
    Route::post('/submit',      [WizardController::class, 'submit'])->name('wizard.submit');
    Route::post('/cancel',      [WizardController::class, 'cancel'])->name('wizard.cancel');
});
