<?php

namespace Tests\Feature;

use Illuminate\Testing\TestResponse;
use Square1\Mpp\Metering\SessionStore;
use Square1\Mpp\Support\Base64Url;
use Tests\TestCase;

class ScoreApiTest extends TestCase
{
    private const STRIPE_NETWORK_ID = 'profile_test_payforgoals';

    private const TEMPO_RECIPIENT = '0x1111111111111111111111111111111111111111';

    private const TEMPO_TOKEN = '0x20c0000000000000000000000000000000000000';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set([
            'mpp.allow_insecure' => true,
            'mpp.methods.stripe.network_id' => self::STRIPE_NETWORK_ID,
            'mpp.methods.tempo.recipient' => self::TEMPO_RECIPIENT,
            'mpp.methods.tempo.token' => self::TEMPO_TOKEN,
            'mpp.methods.tempo.chain_id' => 42431,
        ]);
    }

    public function test_trial_endpoint_needs_no_payment_and_returns_the_first_score(): void
    {
        $this->getJson('/api/v1/scores/trial')
            ->assertOk()
            ->assertJsonPath('tier', 'trial')
            ->assertJsonPath('scoreline.id', 1)
            ->assertJsonPath('scoreline.teams', null)
            ->assertJsonStructure(['scoreline' => ['id', 'home_score', 'away_score', 'teams'], 'note']);
    }

    public function test_trial_endpoint_is_deterministic_and_hides_team_names(): void
    {
        $first = $this->getJson('/api/v1/scores/trial')->json();
        $second = $this->getJson('/api/v1/scores/trial')->json();

        $this->assertSame($first['scoreline']['id'], $second['scoreline']['id']);
        $this->assertNull($first['scoreline']['teams']);
        $this->assertIsInt($first['scoreline']['home_score']);
        $this->assertIsInt($first['scoreline']['away_score']);
    }

    public function test_match_offers_stripe_and_tempo_in_one_402(): void
    {
        $response = $this->getJson('/api/v1/scores/match/1')
            ->assertStatus(402)
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonPath('type', 'https://paymentauth.org/problems/payment-required')
            ->assertJsonPath('status', 402)
            ->assertJsonMissingPath('accepts');

        $challenges = $this->challengesByMethod($response);

        $this->assertSame(['tempo', 'stripe'], array_keys($challenges));
        $this->assertNotSame($challenges['stripe']['id'], $challenges['tempo']['id']);
        $this->assertSame('charge', $challenges['stripe']['intent']);
        $this->assertSame('charge', $challenges['tempo']['intent']);

        $this->assertSame([
            'amount' => '100',
            'currency' => 'usd',
            'methodDetails' => [
                'networkId' => self::STRIPE_NETWORK_ID,
                'paymentMethodTypes' => ['card'],
            ],
        ], $this->decodeParameter($challenges['stripe'], 'request'));

        $tempoRequest = $this->decodeParameter($challenges['tempo'], 'request');
        $this->assertSame('1000000', $tempoRequest['amount']);
        $this->assertSame(self::TEMPO_TOKEN, $tempoRequest['currency']);
        $this->assertSame(self::TEMPO_RECIPIENT, $tempoRequest['recipient']);
        $this->assertSame(42431, $tempoRequest['methodDetails']['chainId']);
        $this->assertSame(['pull'], $tempoRequest['methodDetails']['supportedModes']);
        $this->assertMatchesRegularExpression('/^0x[0-9a-f]{64}$/', $tempoRequest['methodDetails']['memo']);

        foreach ($challenges as $challenge) {
            $opaque = $this->decodeParameter($challenge, 'opaque');
            $this->assertSame('match', $opaque['scope']);
            $this->assertArrayNotHasKey('grants', $opaque);
        }
    }

    public function test_accept_payment_filters_and_orders_the_offered_rails(): void
    {
        $tempoOnly = $this->withHeader('Accept-Payment', 'tempo/charge')
            ->getJson('/api/v1/scores/match/1');
        $stripeOnly = $this->withHeader('Accept-Payment', 'stripe/charge')
            ->getJson('/api/v1/scores/match/1');
        $ranked = $this->withHeader('Accept-Payment', 'tempo/charge, stripe/charge;q=0.2')
            ->getJson('/api/v1/scores/match/1');
        $unsupported = $this->withHeader('Accept-Payment', 'solana/charge')
            ->getJson('/api/v1/scores/match/1');

        $this->assertSame(['tempo'], array_keys($this->challengesByMethod($tempoOnly)));
        $this->assertSame(['stripe'], array_keys($this->challengesByMethod($stripeOnly)));
        $this->assertSame(['tempo', 'stripe'], array_keys($this->challengesByMethod($ranked)));
        $this->assertSame(['tempo', 'stripe'], array_keys($this->challengesByMethod($unsupported)));
    }

    public function test_missing_match_is_rejected_before_a_challenge_is_minted(): void
    {
        $this->getJson('/api/v1/scores/match/999999')
            ->assertNotFound()
            ->assertHeaderMissing('WWW-Authenticate')
            ->assertJsonPath('error', 'No such scoreline.');
    }

    public function test_decade_pass_offers_both_rails_with_one_shared_scope(): void
    {
        $response = $this->getJson('/api/v1/scores/classics/80s')->assertStatus(402);
        $challenges = $this->challengesByMethod($response);

        $this->assertSame(['tempo', 'stripe'], array_keys($challenges));
        $this->assertSame('300', $this->decodeParameter($challenges['stripe'], 'request')['amount']);
        $this->assertSame('3000000', $this->decodeParameter($challenges['tempo'], 'request')['amount']);

        foreach ($challenges as $challenge) {
            $opaque = $this->decodeParameter($challenge, 'opaque');
            $this->assertSame('classics', $opaque['scope']);
            $this->assertSame('3', $opaque['grants']);
        }
    }

    public function test_unknown_decade_does_not_exist(): void
    {
        $this->getJson('/api/v1/scores/classics/70s')->assertNotFound();
    }

    public function test_a_decade_pass_unlocks_three_decades_then_exhausts(): void
    {
        $session = app(SessionStore::class)->create(scope: 'classics', remaining: 3, ttl: 3600);
        $auth = sprintf('Payment session="%s"', $session->id);

        foreach (['80s', '90s', '00s'] as $decade) {
            $this->withHeaders(['Authorization' => $auth])
                ->getJson("/api/v1/scores/classics/{$decade}")
                ->assertOk()
                ->assertJsonPath('tier', 'decade-pass')
                ->assertJsonPath('decade', $decade)
                ->assertJsonPath('pass.scope', 'classics')
                ->assertJsonPath('pass.session', $session->id);
        }

        $this->withHeaders(['Authorization' => $auth])
            ->getJson('/api/v1/scores/classics/80s')
            ->assertStatus(402);
    }

    public function test_a_session_cannot_be_spent_outside_its_scope(): void
    {
        $session = app(SessionStore::class)->create(scope: 'something-else', remaining: 3, ttl: 3600);

        $this->withHeaders(['Authorization' => sprintf('Payment session="%s"', $session->id)])
            ->getJson('/api/v1/scores/classics/90s')
            ->assertStatus(402);
    }

    public function test_discovery_advertises_both_rails_for_each_paid_endpoint(): void
    {
        $document = $this->getJson('/openapi.json')->assertOk()->json();

        $matchOffers = $document['paths']['/api/v1/scores/match/{id}']['get']['x-payment-info']['offers'];
        $classicsOffers = $document['paths']['/api/v1/scores/classics/{decade}']['get']['x-payment-info']['offers'];

        $this->assertSame(['tempo', 'stripe'], array_column($matchOffers, 'method'));
        $this->assertSame(['1000000', '100'], array_column($matchOffers, 'amount'));
        $this->assertSame(['tempo', 'stripe'], array_column($classicsOffers, 'method'));
        $this->assertSame(['3000000', '300'], array_column($classicsOffers, 'amount'));
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function challengesByMethod(TestResponse $response): array
    {
        $challenges = [];

        // A multi-rail 402 puts each Payment challenge on its own header line
        // (laravel-mpp >= 2.1.0), so collect every line. A plain get() would
        // return only the first rail. Comma-joined challenges on one line are
        // still split for good measure.
        $entries = [];
        foreach ($response->headers->all('WWW-Authenticate') as $header) {
            array_push($entries, ...preg_split('/(?:^|,\s*)Payment\s+/', (string) $header, flags: PREG_SPLIT_NO_EMPTY));
        }

        foreach ($entries as $entry) {
            preg_match_all('/([a-z][a-z0-9_-]*)="((?:[^"\\\\]|\\\\.)*)"/i', $entry, $matches, PREG_SET_ORDER);

            $parameters = [];
            foreach ($matches as $match) {
                $parameters[$match[1]] = stripcslashes($match[2]);
            }

            if (isset($parameters['method'])) {
                $challenges[$parameters['method']] = $parameters;
            }
        }

        return $challenges;
    }

    /**
     * @param  array<string, string>  $challenge
     * @return array<string, mixed>
     */
    private function decodeParameter(array $challenge, string $parameter): array
    {
        $decoded = Base64Url::decode($challenge[$parameter] ?? '');
        $value = $decoded === null ? null : json_decode($decoded, true);

        $this->assertIsArray($value, "The {$parameter} challenge parameter must contain base64url JSON.");

        return $value;
    }
}
