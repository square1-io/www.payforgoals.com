<?php

namespace Tests\Feature;

use Square1\Mpp\Metering\SessionStore;
use Tests\TestCase;

class ScoreApiTest extends TestCase
{
    public function test_trial_endpoint_needs_no_payment_and_returns_the_first_score(): void
    {
        $this->getJson('/api/v1/scores/trial')
            ->assertOk()
            ->assertJsonPath('tier', 'trial')
            ->assertJsonPath('scoreline.id', 1)            // always the first score
            ->assertJsonPath('scoreline.teams', null)
            ->assertJsonStructure(['scoreline' => ['id', 'home_score', 'away_score', 'teams'], 'note']);
    }

    public function test_trial_endpoint_is_deterministic_and_hides_team_names(): void
    {
        // Same score every time: the free trial reveals the API shape without
        // leaking the catalogue (hitting it repeatedly never yields a new score).
        $first = $this->getJson('/api/v1/scores/trial')->json();
        $second = $this->getJson('/api/v1/scores/trial')->json();

        $this->assertSame($first['scoreline']['id'], $second['scoreline']['id']);
        $this->assertNull($first['scoreline']['teams']);
        $this->assertIsInt($first['scoreline']['home_score']);
        $this->assertIsInt($first['scoreline']['away_score']);
    }

    public function test_tempo_pay_per_view_is_gated_with_an_mppx_dialect_402(): void
    {
        $response = $this->getJson('/api/v1/tempo/scores/match/1');

        $response->assertStatus(402)
            ->assertHeader('Content-Type', 'application/problem+json');

        // The Tempo rail speaks the mppx dialect: a base64 `request` blob in the
        // header, method="tempo", and NO native `accepts[]` array in the body.
        $challenge = $response->headers->get('WWW-Authenticate');
        $this->assertStringContainsString('method="tempo"', $challenge);
        $this->assertStringContainsString('request="', $challenge);

        $response->assertJsonPath('status', 402)
            ->assertJsonMissingPath('accepts');
    }

    public function test_stripe_pay_per_view_is_gated_with_a_native_402_at_one_dollar(): void
    {
        $response = $this->getJson('/api/v1/stripe/scores/match/1');

        $response->assertStatus(402)
            ->assertHeader('Content-Type', 'application/problem+json');

        // The Stripe rail speaks the native MPP dialect: a signed `accepts[]`
        // entry with method="stripe", and NO mppx base64 `request` blob.
        $challenge = $response->headers->get('WWW-Authenticate');
        $this->assertStringContainsString('method="stripe"', $challenge);
        $this->assertStringNotContainsString('request="', $challenge);

        $response->assertJsonPath('accepts.0.method', 'stripe')
            ->assertJsonPath('accepts.0.amount', '1.00')   // ≥ Stripe's ~$0.50 card minimum
            ->assertJsonPath('accepts.0.currency', 'USD');
    }

    public function test_tempo_decade_pass_is_gated(): void
    {
        $this->getJson('/api/v1/tempo/scores/classics/80s')
            ->assertStatus(402)
            ->assertJsonPath('status', 402);
    }

    public function test_unknown_decade_does_not_exist(): void
    {
        // Outside the route constraint (80s|90s|00s) → no matching route → 404.
        $this->getJson('/api/v1/tempo/scores/classics/70s')->assertNotFound();
    }

    public function test_a_decade_pass_session_unlocks_all_three_decades_then_exhausts(): void
    {
        // Issue a 3-credit session bound to the tempo classics scope, as a paid
        // Decade Pass purchase would (settlement itself is exercised on-chain in
        // the live verification, not here).
        $store = app(SessionStore::class);
        $session = $store->create(scope: 'tempo.classics', remaining: 3, ttl: 3600);
        $auth = sprintf('Payment session="%s"', $session->id);

        foreach (['80s', '90s', '00s'] as $decade) {
            $this->withHeaders(['Authorization' => $auth])
                ->getJson("/api/v1/tempo/scores/classics/{$decade}")
                ->assertOk()
                ->assertJsonPath('tier', 'decade-pass')
                ->assertJsonPath('decade', $decade)
                ->assertJsonPath('pass.scope', 'tempo.classics');
        }

        // Fourth call: credits spent → re-challenged with a 402.
        $this->withHeaders(['Authorization' => $auth])
            ->getJson('/api/v1/tempo/scores/classics/80s')
            ->assertStatus(402);
    }

    public function test_stripe_decade_pass_session_uses_the_stripe_scope(): void
    {
        $store = app(SessionStore::class);
        $session = $store->create(scope: 'stripe.classics', remaining: 1, ttl: 3600);

        $this->withHeaders(['Authorization' => sprintf('Payment session="%s"', $session->id)])
            ->getJson('/api/v1/stripe/scores/classics/90s')
            ->assertOk()
            ->assertJsonPath('tier', 'decade-pass')
            ->assertJsonPath('pass.scope', 'stripe.classics');
    }

    public function test_a_session_cannot_be_spent_outside_its_scope(): void
    {
        $store = app(SessionStore::class);
        $session = $store->create(scope: 'something-else', remaining: 3, ttl: 3600);

        // Wrong scope → the classics gate rejects it and re-challenges.
        $this->withHeaders(['Authorization' => sprintf('Payment session="%s"', $session->id)])
            ->getJson('/api/v1/tempo/scores/classics/90s')
            ->assertStatus(402);
    }
}
