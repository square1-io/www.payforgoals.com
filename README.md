# PayForGoals

> Relive football's greatest scorelines on demand. We return the score, and only the score. Team names are a premium feature, coming soon.

PayForGoals is a small, deployable Laravel app that doubles as a live demo of the [Machine Payments Protocol (MPP)](https://mpp.dev). Its paid API endpoints are gated by the [`square1/laravel-mpp`](https://github.com/square1-io/laravel-mpp) package and settle real payments over two rails:

- Tempo: on-chain stablecoin (pathUSD) on the Tempo testnet. The mppx wire dialect, paid by a stock [`npx mppx`](https://mpp.dev) agent.
- Stripe: cards via [Shared Payment Tokens](https://docs.stripe.com/agentic-commerce/concepts/shared-payment-tokens) (SPTs). The native MPP dialect, settled inline as a PaymentIntent.

Same scores, two ways to pay. The landing page starts with the product conceit, then turns into a straight explainer of MPP and how to pay one of its requests yourself.

Built by [Square1](https://www.square1.io).

## What's in the box

- A marketing and education landing page (single Blade view, responsive, Tailwind v4): hero, API reference, premium teaser, an "is this for real?" turn, a tabbed getting-started section (Tempo and Stripe, both live), and a footer.
- A real, payment-gated JSON API (`routes/api.php`): the same resource under a `/tempo/` rail and a `/stripe/` rail.
- The famous scorelines themselves (`app/Data/Scorelines.php`): scores only, no team names.

## The API

All endpoints return scorelines without team names, with `home_score` and `away_score` as separate integer fields. The free endpoint is rail-agnostic; the paid resource is exposed under each rail by prefix, differing only by how payment is asked.

| Endpoint | Rail | Price | Notes |
|----------|------|-------|-------|
| `GET /api/v1/scores/random` | free | none | A random classic. No payment. |
| `GET /api/v1/tempo/scores/match/{id}` | Tempo | `0.01` pathUSD | One specific match; settled on-chain. |
| `GET /api/v1/tempo/scores/classics/{decade}` | Tempo | `0.05` pathUSD | Decade Pass: one payment grants 3 accesses. |
| `GET /api/v1/stripe/scores/match/{id}` | Stripe | `$1.00` USD | One specific match; settled via PaymentIntent. |
| `GET /api/v1/stripe/scores/classics/{decade}` | Stripe | `$3.00` USD | Decade Pass: one payment grants 3 accesses. |

`decade` is one of `80s|90s|00s`. Decade Pass is metered: one payment issues a reusable `Payment-Session` good for 3 accesses across the decades.

Paid routes are gated by the package middleware in `routes/api.php`. The only difference between the rails is `method=`:

```php
// Tempo rail
->middleware('mpp:0.01,USD,method=tempo,scope=tempo.match,preconditions=matchchecker')   // pay-per-view
->middleware('mpp:0.05,USD,method=tempo,grants=3,scope=tempo.classics')                  // Decade Pass

// Stripe rail ($1 clears Stripe's ~$0.50 per-charge card minimum)
->middleware('mpp:1.00,USD,method=stripe,scope=stripe.match,preconditions=matchchecker') // pay-per-view
->middleware('mpp:3.00,USD,method=stripe,grants=3,scope=stripe.classics')                // Decade Pass
```

### Not charging for a miss

A request for a scoreline that does not exist should never be charged for. The match routes carry a precondition, `preconditions=matchchecker`, which runs before the payment gate:

```php
->middleware('mpp:1.00,USD,method=stripe,scope=stripe.match,preconditions=matchchecker');
```

`App\Mpp\Checks\MatchChecker` looks the id up in `Scorelines` and returns a 404 when it is missing. Because preconditions run before a 402 is minted or a payment settled, a request for `match/999999` gets a plain 404 up front: the buyer is never told to pay for a score that does not exist, and a paid retry never settles a charge. Preconditions are a feature of `square1/laravel-mpp`; this app registers `matchchecker` under `mpp.preconditions.checks` in `config/mpp.php` and attaches it per route.

### Paying a Tempo request (stock `npx mppx` agent)

```bash
# create + fund a Tempo testnet wallet (once)
npx mppx account create
npx mppx account fund --network testnet

# pay-per-view: mppx handles the 402, pay, retry loop for you
npx mppx https://your-host/api/v1/tempo/scores/match/1 --network testnet --account main
```

Tempo receipts contain an on-chain transaction hash. The recipient wallet shown by the demo can be inspected on the Tempo testnet explorer:

```text
https://explore.testnet.tempo.xyz/address/${TEMPO_RECIPIENT}
```

Decade Pass, pay once and reuse the session:

```bash
# 1. pay once; the response carries a 3-credit Payment-Session header
npx mppx https://your-host/api/v1/tempo/scores/classics/80s --network testnet --account main -i
#   -> Payment-Session: id="sess_…", remaining="2", scope="tempo.classics"

# 2. reuse the session on the sibling decades, no new payment
curl https://your-host/api/v1/tempo/scores/classics/90s -H 'Authorization: Payment session="sess_…"'
curl https://your-host/api/v1/tempo/scores/classics/00s -H 'Authorization: Payment session="sess_…"'
```

### Paying a Stripe request (Stripe Link / Shared Payment Tokens)

An unpaid `/stripe/` request returns the native MPP `402`: a signed `accepts[]` entry with `method="stripe"`. The buyer settles it with a Shared Payment Token (SPT) and retries; the package creates and confirms a PaymentIntent inline and serves the resource. No webhooks: settlement is one synchronous round-trip (`confirm: true`, trusted only on `status === 'succeeded'`, idempotency-keyed on the challenge).

You can satisfy the Stripe challenge two ways. In production the buyer wallet is Stripe Link, which as of June 2026 is US-gated: it needs a US Link account and consumer approval for every spend. For development from anywhere, you can mint a test SPT yourself from any Stripe test account with curl and pay with it; a test SPT settles exactly like a Link-minted one.

Production buyer (Stripe Link, US-gated as of June 2026), driven by [`stripe link-cli`](https://github.com/stripe/link-cli), the Stripe analog of `npx mppx`:

```bash
link-cli auth login                       # once: connect a Link account (approve the device in Link)
link-cli spend-request create \
  --network-id profile_... \
  --amount 100 \
  --credential-type shared_payment_token
# then approve the spend in the Link app
link-cli mpp pay <APP_URL>/api/v1/stripe/scores/match/1 \
    --spend-request-id lsrq_...           # presents the SPT; the server settles inline, 200 + receipt
```

Link is consumer-consent: a person approves every spend, even in test mode, and the challenge advertises the seller's `STRIPE_NETWORK_ID` (a `profile_…` from the Stripe Dashboard) so the wallet can scope the token to you. That approval step is the agentic-commerce story, and the main thing that sets Link apart from headless Tempo, where the client signs and pays its own gas.

Self-minted test SPT (any Stripe test account, no Link, no US gate). Fetch the challenge, mint a token with a test card, then replay with the token and the challenge's `sig`:

```bash
# 1. Fetch the 402; copy its challengeId and the stripe accept's sig.
curl -s <APP_URL>/api/v1/stripe/scores/match/1

# 2. Mint a test SPT for the $1.00 challenge (100 minor units). pm_card_visa is Stripe's always-succeeds test card.
curl -s -u "sk_test_...:" -H "Stripe-Version: 2026-05-27.preview" \
  -X POST https://api.stripe.com/v1/test_helpers/shared_payment/granted_tokens \
  -d payment_method=pm_card_visa \
  -d "usage_limits[currency]=usd" \
  -d "usage_limits[max_amount]=100" \
  -d "usage_limits[expires_at]=$(($(date +%s)+300))"
#   -> { "id": "spt_…", … }

# 3. Replay with the token and the echoed sig -> 200 + Payment-Receipt.
curl -si <APP_URL>/api/v1/stripe/scores/match/1 \
  -H 'Authorization: Payment method="stripe", challengeId="chal_…", sig="…", spt="spt_…"'
```

> The Stripe 402 is emitted out of the box; settling needs the app's seller `STRIPE_SECRET_KEY`. `STRIPE_NETWORK_ID` is only needed for the Link path, so a wallet can scope a token to you; the self-minted test SPT flow above settles without it, because test-mode SPTs are not seller-scoped.

## Running locally

Requires PHP 8.4+, Composer, and Node.

The MPP package (`square1/laravel-mpp`, `^1.1`) is on Packagist and already declared in `composer.json`, so a standard install pulls it in:

```bash
composer install
npm install

cp .env.example .env
php artisan key:generate
php -r "echo 'MPP_CHALLENGE_SECRET=' . bin2hex(random_bytes(32)) . PHP_EOL;" >> .env   # then set it in .env

php artisan migrate
npm run build               # or: npm run dev
php artisan serve --host=127.0.0.1 --port=8000
```

## Environment variables

| Var | Required | Purpose |
|-----|----------|---------|
| `APP_KEY` | yes | Standard Laravel app key (`php artisan key:generate`). |
| `APP_NAME` | no | Drives the brand name shown across the site (default `PayForGoals`). |
| `APP_URL` | yes (deploy) | Public URL; used in the landing page's copy-paste commands and as the 402 realm. |
| `MPP_CHALLENGE_SECRET` | yes | HMAC key that signs/binds payment challenges (both rails). Treat like `APP_KEY`: strong, random, stable. |
| `MPP_DEFAULT_METHOD` | no | Fallback rail for routes that don't pin `method=`. Routes here pin it explicitly, so this is rarely consulted. |
| `MPP_SESSION_DRIVER` | no | `cache` (default) or `database`. Decade Pass sessions live here. |
| Tempo rail | | |
| `TEMPO_RECIPIENT` | for Tempo | Wallet address funds settle to. |
| `TEMPO_RPC_URL` | no | Tempo JSON-RPC endpoint (default `https://rpc.moderato.tempo.xyz`). |
| `TEMPO_CHAIN_ID` | no | Chain id (default `42431`, Tempo testnet). |
| `TEMPO_TOKEN` | no | pathUSD token address (default `0x20c0…0000`). |
| `TEMPO_DECIMALS` | no | Token decimals (default `6`). |
| Stripe rail | | |
| `STRIPE_SECRET_KEY` | for Stripe settlement | Stripe test secret (`sk_test_…`). Required to settle `/stripe/` routes; the 402 challenge is emitted without it. |
| `STRIPE_NETWORK_ID` | for Stripe Link | Stripe MPP Network/Profile ID (`profile_…`), advertised in the 402 so a Link / agent wallet can scope an SPT to this seller. Created in the Stripe Dashboard (agentic commerce). |

The Tempo rail needs no server key: the client signs the pathUSD transfer and pays its own gas; the server only broadcasts and confirms it via RPC. The Stripe rail needs `STRIPE_SECRET_KEY` only to settle the PaymentIntent (no webhook secret; settlement is inline).

## Deploying to Laravel Cloud

Standard Laravel with Vite and Tailwind. No exotic dependencies.

1. Connect the repo. Laravel Cloud runs `composer install` and `npm run build`. The MPP package resolves from Packagist, so no deploy keys or repository config are needed.
2. Set env vars (above). At minimum: `APP_KEY`, `APP_URL`, `MPP_CHALLENGE_SECRET`, and the rail config for whichever rails you're enabling (`TEMPO_RECIPIENT` for Tempo, `STRIPE_SECRET_KEY` for Stripe settlement).
3. Pick a session store. `MPP_SESSION_DRIVER=cache` works with any cache backend; for oversell-proof metering under real concurrency, point it at Redis.
4. Run migrations on deploy (`php artisan migrate --force`).

## How MPP works (the short version)

1. An unpaid request to a gated route returns HTTP 402 Payment Required with a signed challenge. Tempo uses the mppx dialect (a base64 `request` blob); Stripe uses the native MPP dialect (a signed `accepts[]` entry).
2. The client (an AI agent, `npx mppx`, and so on) settles the challenge. Over Tempo it signs a pathUSD transfer; over Stripe it presents a Shared Payment Token. It then retries the same request with an `Authorization: Payment …` credential.
3. The server verifies settlement (broadcasting and confirming the on-chain transfer, or creating and confirming a PaymentIntent, both inline, no webhooks), serves the resource, and returns a `Payment-Receipt`. Metered routes also issue a `Payment-Session` the client reuses until its credits run out.

No accounts, no checkout, no stored cards. The agent pays per request, in the moment. See [`square1/laravel-mpp`](https://github.com/square1-io/laravel-mpp) for the full protocol and both rails.

## License

MIT. PayForGoals is a demo; the scorelines are real, and the missing team names are doing a lot of work. Both rails are live: Tempo headless on testnet, Stripe via Link with consumer approval (set `STRIPE_SECRET_KEY` and `STRIPE_NETWORK_ID` to settle).
