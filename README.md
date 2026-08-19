# PayForGoals

> Relive football's greatest scorelines on demand. We return the score, and only the score. Team names are a premium feature, coming soon.

PayForGoals is a small, deployable Laravel app that doubles as a live demo of the [Machine Payments Protocol (MPP)](https://mpp.dev). Its paid API endpoints are gated by the [`square1/laravel-mpp`](https://github.com/square1-io/laravel-mpp) package and offer two payment methods from one endpoint:

- Stripe: cards via [Shared Payment Tokens](https://docs.stripe.com/agentic-commerce/concepts/shared-payment-tokens) (SPTs), settled inline as a PaymentIntent.
- Tempo: on-chain stablecoin (pathUSD) on the Tempo testnet, paid by a stock [`npx mppx`](https://mpp.dev) agent.

Both methods use the same MPP wire format. One `402` contains one `WWW-Authenticate: Payment` challenge per method, and the agent chooses one.

Built by [Square1](https://www.square1.io).

## What's in the box

- A marketing and education landing page (single Blade view, responsive, Tailwind v4): hero, API reference, premium teaser, an "is this for real?" turn, a tabbed getting-started section (Tempo and Stripe, both live), and a footer.
- A real, payment-gated JSON API (`routes/api.php`): one paid resource, payable through Stripe or Tempo from the same `402`.
- The famous scorelines themselves (`app/Data/Scorelines.php`): scores only, no team names.

## The API

All endpoints return scorelines without team names, with `home_score` and `away_score` as separate integer fields. The free endpoint needs no payment. Each paid endpoint offers both methods.

| Endpoint | Price | Notes |
|----------|-------|-------|
| `GET /api/v1/scores/trial` | free | One free score (the first), to inspect the API shape. |
| `GET /api/v1/scores/match/{id}` | `$1.00` USD | One specific match. Pay through Stripe or Tempo. |
| `GET /api/v1/scores/classics/{decade}` | `$3.00` USD | Decade Pass: one payment grants three accesses. Either method. |

`decade` is one of `80s|90s|00s`. Decade Pass is metered: one payment issues a reusable `Payment-Session` good for 3 accesses across the decades.

Paid routes are gated by the package middleware in `routes/api.php`. The `methods=` argument controls what one route offers:

```php
->middleware('mpp:1.00,USD,methods=stripe|tempo,scope=match,preconditions=matchchecker')
->middleware('mpp:3.00,USD,methods=stripe|tempo,grants=3,scope=classics')
```

The optional request header `Accept-Payment: tempo/charge` or `Accept-Payment: stripe/charge` filters the challenges returned to a capable client. Without it, both are returned.

### Not charging for a miss

A request for a scoreline that does not exist should never be charged for. The match routes carry a precondition, `preconditions=matchchecker`, which runs before the payment gate:

```php
->middleware('mpp:1.00,USD,methods=stripe|tempo,scope=match,preconditions=matchchecker');
```

`App\Mpp\Checks\MatchChecker` looks the id up in `Scorelines` and returns a 404 when it is missing. Because preconditions run before a 402 is minted or a payment settled, a request for `match/999999` gets a plain 404 up front: the buyer is never told to pay for a score that does not exist, and a paid retry never settles a charge. Preconditions are a feature of `square1/laravel-mpp`; this app registers `matchchecker` under `mpp.preconditions.checks` in `config/mpp.php` and attaches it per route.

### Paying a request over Tempo

```bash
# create + fund a Tempo testnet wallet (once)
npx mppx account create
npx mppx account fund --network testnet

# pay-per-view: select Tempo, then mppx handles the 402, pay, retry loop for you
npx mppx https://your-host/api/v1/scores/match/1 \
  -H 'Accept-Payment: tempo/charge' \
  --network testnet --account main
```

Tempo receipts contain an on-chain transaction hash. The recipient wallet shown by the demo can be inspected on the Tempo testnet explorer:

```text
https://explore.testnet.tempo.xyz/address/${TEMPO_RECIPIENT}
```

Decade Pass, pay once and reuse the session:

```bash
# 1. pay once; the response carries a 3-credit Payment-Session header
npx mppx https://your-host/api/v1/scores/classics/80s \
  -H 'Accept-Payment: tempo/charge' \
  --network testnet --account main -i
#   -> Payment-Session: id="sess_…", remaining="2", scope="classics"

# 2. reuse the session on the sibling decades, no new payment
curl https://your-host/api/v1/scores/classics/90s -H 'Authorization: Payment session="sess_…"'
curl https://your-host/api/v1/scores/classics/00s -H 'Authorization: Payment session="sess_…"'
```

### Paying a request over Stripe

The same `402` carries a `method="stripe"` challenge. Its base64url `request` parameter contains the amount in minor units, currency, and seller network profile. The buyer settles it with a Shared Payment Token (SPT) and retries; the package creates and confirms a PaymentIntent inline.

You can satisfy the Stripe challenge two ways. In production the buyer wallet is Stripe Link, which as of June 2026 is US-gated: it needs a US Link account and consumer approval for every spend. For development from anywhere, you can mint a test SPT yourself from any Stripe test account with curl and pay with it; a test SPT settles exactly like a Link-minted one.

Stripe Link buyer (US-gated), using the current end-to-end [`stripe link-cli`](https://github.com/stripe/link-cli) flow:

```bash
link-cli auth login                       # once: connect a Link account (approve the device in Link)
link-cli mpp pay <APP_URL>/api/v1/scores/match/1 \
  --context "Purchase scoreline #1 from PayForGoals for $1.00 USD because the user asked the agent to retrieve this specific football result from the demo API." \
  --test
```

Link is consumer-consent: a person approves every spend, even in test mode, and the challenge advertises the seller's `STRIPE_NETWORK_ID` (a `profile_…` from the Stripe Dashboard) so the wallet can scope the token to you. That approval step is the agentic-commerce story, and the main thing that sets Link apart from headless Tempo, where the client signs and pays its own gas.

Self-minted test SPT (any Stripe test account, no Link, no US gate). Fetch the challenge, mint a token with a test card, then let an MPP client encode the paid retry:

```bash
# 1. Fetch the 402 and decode the Stripe challenge's request parameter.
curl -si <APP_URL>/api/v1/scores/match/1 -H 'Accept-Payment: stripe/charge'

# 2. Mint a test SPT for the $1.00 challenge (100 minor units). pm_card_visa is Stripe's always-succeeds test card.
curl -s -u "sk_test_...:" -H "Stripe-Version: 2026-05-27.preview" \
  -X POST https://api.stripe.com/v1/test_helpers/shared_payment/granted_tokens \
  -d payment_method=pm_card_visa \
  -d "usage_limits[currency]=usd" \
  -d "usage_limits[max_amount]=100" \
  -d "usage_limits[expires_at]=$(($(date +%s)+300))" \
  -d "seller_details[network_id]=profile_..."
#   -> { "id": "spt_…", … }

# seller_details is optional. When present, use the networkId decoded from the
# Stripe challenge to scope the test SPT to this seller.

# 3. Replay with a client-encoded MPP credential -> 200 + Payment-Receipt.
curl -si <APP_URL>/api/v1/scores/match/1 \
  -H 'Authorization: Payment <base64url {challenge, payload, source}>'
```

> `STRIPE_NETWORK_ID` is required to offer the Stripe method in v2, and `STRIPE_SECRET_KEY` is required to settle it. The network ID is advertised in the challenge so Link or another wallet can scope its SPT to this seller.

## Running locally

Requires PHP 8.4+, Composer, and Node.

The MPP package (`square1/laravel-mpp`, `2.0.0`) is installed from Packagist and declared in `composer.json`:

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
| `MPP_DEFAULT_METHOD` | no | Primary method for routes that do not declare their own method set. |
| `MPP_SESSION_DRIVER` | no | `cache` (default) or `database`. Decade Pass sessions live here. |
| `MPP_CACHE_STORE` | no | Cache store for challenges and replay protection. Unset inherits Laravel's `CACHE_STORE`. Use a shared store in multi-instance deployments. |
| `MPP_SESSION_CACHE_STORE` | no | Optional session-cache override. Unset inherits Laravel's `CACHE_STORE`. |
| Tempo rail | | |
| `TEMPO_RECIPIENT` | for Tempo | Wallet address funds settle to. |
| `TEMPO_RPC_URL` | no | Tempo JSON-RPC endpoint (default `https://rpc.moderato.tempo.xyz`). |
| `TEMPO_CHAIN_ID` | no | Chain id (default `42431`, Tempo testnet). |
| `TEMPO_TOKEN` | no | pathUSD token address (default `0x20c0…0000`). |
| `TEMPO_DECIMALS` | no | Token decimals (default `6`). |
| Stripe rail | | |
| `STRIPE_SECRET_KEY` | for Stripe settlement | Stripe test secret (`sk_test_…`) used to settle the SPT inline. |
| `STRIPE_NETWORK_ID` | to offer Stripe | Stripe Network/Profile ID (`profile_…`), advertised in the challenge so a wallet can scope an SPT to this seller. |

The Tempo rail needs no server key: the client signs the pathUSD transfer and pays its own gas; the server only broadcasts and confirms it via RPC. The Stripe rail needs `STRIPE_SECRET_KEY` only to settle the PaymentIntent (no webhook secret; settlement is inline).

## Deploying to Laravel Cloud

Standard Laravel with Vite and Tailwind. No exotic dependencies.

1. Connect the repo. Laravel Cloud runs `composer install` and `npm run build`. The MPP package resolves from Packagist, so no deploy keys or repository config are needed.
2. Set env vars (above). At minimum: `APP_KEY`, `APP_URL`, `MPP_CHALLENGE_SECRET`, and the rail config for whichever rails you're enabling (`TEMPO_RECIPIENT` for Tempo, `STRIPE_SECRET_KEY` for Stripe settlement).
3. Pick a session store. `MPP_SESSION_DRIVER=cache` works with any cache backend; for oversell-proof metering under real concurrency, point it at Redis.
4. Run migrations on deploy (`php artisan migrate --force`).

## How MPP works (the short version)

1. An unpaid request returns HTTP 402 with one `WWW-Authenticate: Payment` challenge per offered method. Each challenge has its own base64url, method-specific `request` payload.
2. The client picks one challenge. Over Tempo it signs a pathUSD transfer; over Stripe it presents an SPT. It retries the same URL with one `Authorization: Payment …` credential.
3. The server verifies settlement (broadcasting and confirming the on-chain transfer, or creating and confirming a PaymentIntent, both inline, no webhooks), serves the resource, and returns a `Payment-Receipt`. Metered routes also issue a `Payment-Session` the client reuses until its credits run out.

No accounts, no checkout, no stored cards. The agent pays per request, in the moment. See [`square1/laravel-mpp`](https://github.com/square1-io/laravel-mpp) for the full protocol and both rails.

## License

MIT. PayForGoals is a demo; the scorelines are real, and the missing team names are doing a lot of work. Both rails are live: Tempo headless on testnet, Stripe via Link with consumer approval (set `STRIPE_SECRET_KEY` and `STRIPE_NETWORK_ID` to settle).
