<?php

namespace App\Http\Controllers;

use App\Data\Scorelines;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The PayForGoals API. Every endpoint returns a famous scoreline and nothing that
 * would identify who scored it. Payment gating is applied at the route level via
 * the laravel-mpp middleware; by the time a request reaches a paid action here,
 * settlement (or a valid prepaid session) has already been verified upstream.
 */
class ScoreController extends Controller
{
    /** Free. A random classic scoreline. No payment required. */
    public function random(): JsonResponse
    {
        return response()->json([
            'tier' => 'free',
            'scoreline' => Scorelines::present(Scorelines::random()),
        ]);
    }

    /** Pay-per-view. A specific famous match's scoreline. */
    public function match(int $id): JsonResponse
    {
        $entry = Scorelines::find($id);

        if (! $entry) {
            return response()->json([
                'error' => 'No such scoreline.',
                'detail' => "We have no record of match #{$id}. Try /api/v1/scores/random for inspiration.",
            ], 404);
        }

        return response()->json([
            'tier' => 'pay-per-view',
            'scoreline' => Scorelines::present($entry),
        ]);
    }

    /** Decade Pass (metered bundle). All three decades on one payment. */
    public function classics(Request $request, string $decade): JsonResponse
    {
        $valid = ['80s', '90s', '00s'];

        if (! in_array($decade, $valid, true)) {
            return response()->json([
                'error' => 'Unknown decade.',
                'detail' => 'The Decade Pass covers 80s, 90s and 00s.',
            ], 404);
        }

        $scorelines = array_map(
            fn (array $entry) => Scorelines::present($entry),
            Scorelines::forDecade($decade),
        );

        return response()->json([
            'tier' => 'decade-pass',
            'decade' => $decade,
            'count' => count($scorelines),
            'scorelines' => $scorelines,
            'pass' => $this->passInfo($request),
        ]);
    }

    /**
     * Surface the prepaid session state in the body so a human (or agent) can see
     * how many decades remain on the pass. The middleware sets a `Payment-Session`
     * response header with the authoritative remaining count; we mirror what we
     * can read from the inbound credential here for visibility.
     *
     * @return array<string, mixed>
     */
    private function passInfo(Request $request): array
    {
        $auth = (string) $request->header('Authorization', '');

        $session = null;
        if (preg_match('/session="([^"]+)"/', $auth, $m)) {
            $session = $m[1];
        }

        return [
            'scope' => str_starts_with($request->path(), 'api/v1/stripe/')
                ? 'stripe.classics'
                : 'tempo.classics',
            'grantsPerPurchase' => 3,
            'session' => $session,
            'note' => $session
                ? 'Reusing your Decade Pass. See the Payment-Session response header for remaining credits.'
                : 'This decade was unlocked by your purchase. The Payment-Session header carries your remaining credits; reuse it on the other decades with Authorization: Payment session="...".',
        ];
    }
}
