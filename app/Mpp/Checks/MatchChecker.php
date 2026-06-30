<?php

namespace App\Mpp\Checks;

use App\Data\Scorelines;
use Illuminate\Http\Request;
use Square1\Mpp\Payment\PaymentSpec;
use Symfony\Component\HttpFoundation\Response;

/**
 * Precondition: reject a request for a match that does not exist before the
 * payment gate runs, so no challenge is minted and no payment is taken for a
 * scoreline we cannot serve. Registered as `matchchecker` in config/mpp.php and
 * attached to the match routes with `preconditions=matchchecker`.
 */
class MatchChecker
{
    public function check(Request $request, PaymentSpec $spec): ?Response
    {
        $id = (int) $request->route('id');

        if (Scorelines::find($id)) {
            return null;
        }

        return response()->json([
            'error' => 'No such scoreline.',
            'detail' => "We have no record of match #{$id}. Try /api/v1/scores/random for inspiration.",
        ], 404);
    }
}
