<?php

use App\Http\Controllers\ScoreController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| PayForGoals API (v1)
|--------------------------------------------------------------------------
|
| Paid endpoints are gated by the square1/laravel-mpp middleware. The same
| resource is exposed under two rails, distinguished by route prefix:
|   /api/v1/tempo/…   on-chain pathUSD (mppx dialect), settled by `npx mppx`.
|   /api/v1/stripe/…  Shared Payment Tokens (native MPP dialect), priced ≥ $0.50.
| An unpaid request gets a 402 challenge in that rail's dialect; the package
| verifies settlement and serves the resource plus a Payment-Receipt.
|
*/

Route::prefix('v1')->group(function () {
    // Free — rail-agnostic, no payment.
    Route::get('/scores/random', [ScoreController::class, 'random']);

    // ── Tempo rail: on-chain pathUSD, mppx dialect, settled by a stock `npx mppx`
    //    agent (sub-cent pricing; on-chain has no card minimum). ──
    Route::prefix('tempo/scores')->group(function () {
        Route::get('/match/{id}', [ScoreController::class, 'match'])
            ->whereNumber('id')
            ->middleware('mpp:0.01,USD,method=tempo,scope=tempo.match,preconditions=matchchecker');

        Route::get('/classics/{decade}', [ScoreController::class, 'classics'])
            ->where('decade', '80s|90s|00s')
            ->middleware('mpp:0.05,USD,method=tempo,grants=3,scope=tempo.classics');
    });

    // ── Stripe rail: Shared Payment Tokens, native MPP `accepts[]` dialect.
    //    Priced at $1 to clear Stripe's ~$0.50 per-charge card minimum. ──
    Route::prefix('stripe/scores')->group(function () {
        Route::get('/match/{id}', [ScoreController::class, 'match'])
            ->whereNumber('id')
            ->middleware('mpp:1.00,USD,method=stripe,scope=stripe.match,preconditions=matchchecker');

        Route::get('/classics/{decade}', [ScoreController::class, 'classics'])
            ->where('decade', '80s|90s|00s')
            ->middleware('mpp:3.00,USD,method=stripe,grants=3,scope=stripe.classics');
    });
});
