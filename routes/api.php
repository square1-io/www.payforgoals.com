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

    // NOTE: rail order is load-bearing. Clients that do not send an
    // `Accept-Payment` header take the FIRST challenge in the 402 without
    // checking whether they can pay it — `npx mppx` does exactly this. Tempo is
    // listed first so a stock crypto agent works with no extra flags; a card
    // client can still select Stripe with `Accept-Payment: stripe/charge`.
    // Do not reorder without re-testing `npx mppx <url> --network testnet`.

    // The precondition rejects a missing match before any challenge is minted.
    Route::get('/scores/match/{id}', [ScoreController::class, 'match'])
        ->whereNumber('id')
        ->middleware('mpp:1.00,USD,methods=tempo|stripe,scope=match,preconditions=matchchecker');

    // One payment grants three accesses across all supported decades.
    Route::get('/scores/classics/{decade}', [ScoreController::class, 'classics'])
        ->where('decade', '80s|90s|00s')
        ->middleware('mpp:3.00,USD,methods=tempo|stripe,grants=3,scope=classics');
});
