<?php

use App\Http\Controllers\ScoreController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| PayForGoals API (v1)
|--------------------------------------------------------------------------
|
| Paid endpoints are gated by square1/laravel-mpp. One route offers both rails:
| an unpaid request gets a 402 carrying one Payment challenge for Stripe and
| one for Tempo. The client chooses a rail, settles it, and retries the same URL.
|
| One price covers both rails, so paid resources are priced high enough for
| Stripe cards. Tempo pays the same numeric amount in pathUSD.
|
*/

Route::prefix('v1')->group(function () {
    // Free trial: one fixed score (the first), rail-agnostic, no payment.
    Route::get('/scores/trial', [ScoreController::class, 'trial']);

    // The precondition rejects a missing match before any challenge is minted.
    Route::get('/scores/match/{id}', [ScoreController::class, 'match'])
        ->whereNumber('id')
        ->middleware('mpp:1.00,USD,methods=stripe|tempo,scope=match,preconditions=matchchecker');

    // One payment grants three accesses across all supported decades.
    Route::get('/scores/classics/{decade}', [ScoreController::class, 'classics'])
        ->where('decade', '80s|90s|00s')
        ->middleware('mpp:3.00,USD,methods=stripe|tempo,grants=3,scope=classics');
});
