<?php

namespace App\Http\Controllers;

use App\Data\ClassicsResult;
use App\Data\MatchResult;
use App\Data\PassInfo;
use App\Data\Scorelines;
use App\Data\TrialResult;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Square1\Mpp\Attributes\DiscoveryInfo;

/**
 * The PayForGoals API. Every endpoint returns a famous scoreline and nothing that
 * would identify who scored it. Payment gating is applied at the route level via
 * the laravel-mpp middleware; by the time a request reaches a paid action here,
 * settlement (or a valid prepaid session) has already been verified upstream.
 */
class ScoreController extends Controller
{
    #[DiscoveryInfo(
        summary: 'Try one famous scoreline for free',
        description: 'This operation returns one sample. The sample has the same format as the paid match endpoint.',
        tags: ['scorelines'],
        response: TrialResult::class,
    )]
    public function trial(): JsonResponse
    {
        return response()->json(new TrialResult(
            tier: 'trial',
            scoreline: Scorelines::present(Scorelines::first()),
            note: 'This is the free trial score. Fetch any specific match at /api/v1/scores/match/{id}, payable by Stripe or Tempo from one 402.',
        ));
    }

    #[DiscoveryInfo(
        summary: 'Fetch a famous scoreline',
        description: 'This operation returns one scoreline for a match ID. The response does not include team names.',
        priceNote: 'The price is for one scoreline. You can pay with Tempo or Stripe.',
        tags: ['scorelines'],
        parameters: [
            'id' => ['description' => 'The match ID.', 'example' => 1],
        ],
        response: [
            '200' => MatchResult::class,
            '404' => ['description' => 'No scoreline has this match ID.'],
        ],
    )]
    public function match(int $id): JsonResponse
    {
        $entry = Scorelines::find($id);

        if (! $entry) {
            return response()->json([
                'error' => 'No such scoreline.',
                'detail' => "We have no record of match #{$id}. Try /api/v1/scores/trial for a free sample.",
            ], 404);
        }

        return response()->json(new MatchResult(
            tier: 'pay-per-view',
            scoreline: Scorelines::present($entry),
        ));
    }

    #[DiscoveryInfo(
        summary: 'Fetch classic scorelines by decade',
        description: 'This operation returns the scorelines for one decade. The response does not include team names.',
        priceNote: 'One payment gives access to all three decades.',
        tags: ['scorelines'],
        parameters: [
            'decade' => ['description' => 'The decade to return.', 'example' => '80s'],
        ],
        response: [
            '200' => ClassicsResult::class,
            '404' => ['description' => 'PayForGoals does not support this decade.'],
        ],
    )]
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

        return response()->json(new ClassicsResult(
            tier: 'decade-pass',
            decade: $decade,
            count: count($scorelines),
            scorelines: $scorelines,
            pass: $this->passInfo($request),
        ));
    }

    /**
     * Surface the prepaid session state in the body so a human (or agent) can see
     * how many decades remain on the pass. The middleware sets a `Payment-Session`
     * response header with the authoritative remaining count; we mirror what we
     * can read from the inbound credential here for visibility.
     */
    private function passInfo(Request $request): PassInfo
    {
        $auth = (string) $request->header('Authorization', '');

        $session = null;
        if (preg_match('/session="([^"]+)"/', $auth, $m)) {
            $session = $m[1];
        }

        return new PassInfo(
            scope: 'classics',
            grantsPerPurchase: 3,
            session: $session,
            note: $session
                ? 'Reusing your Decade Pass. See the Payment-Session response header for remaining credits.'
                : 'This decade was unlocked by your purchase. The Payment-Session header carries your remaining credits; reuse it on the other decades with Authorization: Payment session="...".',
        );
    }
}
