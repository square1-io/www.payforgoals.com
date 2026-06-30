@php
    $brand = $cfg['brand'];
    $price = $cfg['pricing'];
    $stripe = $price['stripe'];
    $tempo = $cfg['tempo'];
    $links = $cfg['links'];
    $base = rtrim(config('app.url'), '/');
    $tempoExplorerAddress = rtrim($links['tempo_explorer'], '/').'/address/'.$tempo['recipient'];
    // The famous scorelines that flicker across the hero board.
    $boardScores = ['7–1', '3–3', '4–3', '2–1', '5–1', '0–0', '6–1', '3–2'];
@endphp
<!doctype html>
<html lang="en" class="dotgrid">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $brand }} - relive football's greatest scorelines</title>
    <meta name="description" content="{{ $brand }} returns the score, and only the score. A live demo of the Machine Payments Protocol over Tempo, by Square1.">
    <link rel="canonical" href="{{ $base }}/">

    {{-- Open Graph / social sharing --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $brand }}">
    <meta property="og:url" content="{{ $base }}/">
    <meta property="og:title" content="{{ $brand }} - football's greatest scorelines, on demand">
    <meta property="og:description" content="Relive iconic results through a simple API. Under the hood: a live demo of the Machine Payments Protocol - software agents pay per request over Tempo. By Square1.">
    <meta property="og:image" content="{{ $base }}/og.png">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="{{ $brand }} - a football scores API that's a live Machine Payments Protocol demo">

    {{-- Twitter / X --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $brand }} - football's greatest scorelines, on demand">
    <meta name="twitter:description" content="A football scores API that's secretly a live Machine Payments Protocol demo - agents pay per request over Tempo. By Square1.">
    <meta name="twitter:image" content="{{ $base }}/og.png">

    {{-- Favicons --}}
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16.png">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <meta name="theme-color" content="#0a0e14">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased">

{{-- ============================== NAV ============================== --}}
<header class="sticky top-0 z-40 border-b bg-paper/85 backdrop-blur-md" style="border-color: var(--color-line)">
    <div class="mx-auto flex max-w-6xl items-center justify-between px-5 py-4">
        <a href="#top" class="flex items-center gap-2.5">
            <span class="scorechip led text-base tracking-tight">{{ mb_substr($brand, 0, 1) }}–</span>
            <span class="font-display text-lg font-700 tracking-tight text-ink">{{ $brand }}</span>
        </a>
        <nav class="hidden items-center gap-7 text-sm text-ink-soft md:flex font-mono">
            <a href="#api" class="transition hover:text-turf-bright">API</a>
            <a href="#premium" class="transition hover:text-turf-bright">Premium</a>
            <a href="#real" class="transition hover:text-turf-bright">Is this real?</a>
            <a href="#start" class="transition hover:text-turf-bright">Get started</a>
        </nav>
        <a href="#api" class="rounded-md bg-turf px-3.5 py-2 font-display text-sm font-700 text-white transition hover:bg-turf-bright">
            Try it out
        </a>
    </div>
</header>

<main id="top">

{{-- ============================== HERO ============================== --}}
<section class="relative overflow-hidden border-b" style="border-color: var(--color-line)">
    <div class="pointer-events-none absolute inset-0"
         style="background: radial-gradient(120% 80% at 50% -10%, rgba(28,122,91,0.08), transparent 55%);"></div>

    <div class="relative mx-auto max-w-6xl px-5 pt-20 pb-16 md:pt-28 md:pb-24">
        <p class="eyebrow flap" style="animation-delay: .02s">Matchday · On demand</p>

        <h1 class="mt-5 max-w-4xl font-display text-[2.05rem] font-800 leading-[1.02] tracking-tight text-ink sm:text-5xl sm:leading-[0.98] md:text-7xl">
            The greatest scorelines<br>
            in football history.<br>
            <span class="text-turf-bright">On tap.</span>
        </h1>

        {{-- The signature: a live scoreboard of famous scores, on its dark device. --}}
        <div class="board flap mt-12 rounded-xl p-6 md:p-8" style="animation-delay: .12s">
            <div class="flex flex-wrap items-baseline justify-between gap-4">
                <span class="eyebrow" style="color: var(--color-amber-dim)">The board</span>
                <span class="font-mono text-xs" style="color: #6f7d77">names withheld · scores only</span>
            </div>
            <div class="mt-6 grid grid-cols-4 gap-x-4 gap-y-7 sm:gap-x-6 md:grid-cols-8">
                @foreach ($boardScores as $i => $s)
                    <div class="flap text-center" style="animation-delay: {{ 0.18 + $i * 0.05 }}s">
                        <div class="led text-2xl sm:text-3xl md:text-4xl">{{ $s }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="mt-10 flex max-w-2xl flex-col gap-5">
            <p class="text-lg leading-relaxed text-ink md:text-xl">
                Every iconic result, ready to relive in a single request. {{ $brand }} returns the
                scoreline - the comeback, the collapse, the impossible final - straight to your terminal.
            </p>
            <p class="font-mono text-sm leading-relaxed text-ink-soft">
                Same scores, two ways to pay: on-chain over
                <a href="{{ $tempoExplorerAddress }}" class="text-turf-bright underline-offset-4 hover:underline" target="_blank" rel="noreferrer">Tempo</a>,
                or by card over
                <a href="{{ $links['stripe_spt'] }}" class="text-turf-bright underline-offset-4 hover:underline" target="_blank" rel="noreferrer">Stripe</a>.
                Pick the endpoint that matches your agent's wallet.
            </p>
            <p class="font-mono text-sm leading-relaxed text-ink-soft">
                One detail. We return the score, and only the score. Team names are a premium feature,
                <span class="text-turf-bright">coming soon</span>.
            </p>
            <div class="mt-3 flex flex-wrap items-center gap-3">
                <a href="#api" class="rounded-md bg-turf px-5 py-3 font-display font-700 text-white transition hover:bg-turf-bright">
                    Explore the API
                </a>
                <a href="#start" class="rounded-md border px-5 py-3 font-display font-600 text-ink transition hover:border-turf hover:text-turf" style="border-color: var(--color-line)">
                    Pay your first request
                </a>
            </div>
        </div>
    </div>
</section>

{{-- ============================== API REFERENCE ============================== --}}
<section id="api" class="border-b" style="border-color: var(--color-line)">
    <div class="mx-auto max-w-6xl px-5 py-20 md:py-28">
        <p class="eyebrow">How it works</p>
        <h2 class="mt-4 max-w-3xl font-display text-3xl font-800 tracking-tight text-ink md:text-5xl">
            One resource. Two rails. No checkout.
        </h2>
        <p class="mt-5 max-w-2xl leading-relaxed text-ink-soft">
            Free to browse, then pick how your agent pays. The same scoreline is exposed twice -
            under <code class="font-mono font-600 text-turf">/tempo/</code> for on-chain settlement and
            <code class="font-mono font-600 text-turf">/stripe/</code> for cards - differing only by rail.
            Either way the client pays per request: no signup, no checkout page. Copy a command and run it.
        </p>

        {{-- FREE - rail-agnostic, full width --}}
        <article class="panel mt-12 flex min-w-0 flex-col rounded-xl p-6 md:flex-row md:items-center md:gap-8">
            <div class="md:flex-1">
                <div class="flex items-center gap-3">
                    <span class="eyebrow">Free trial · either rail</span>
                    <span class="scorechip led text-base">1</span>
                </div>
                <h3 class="mt-3 font-display text-xl font-700 text-ink">Free trial</h3>
                <p class="mt-2 max-w-md text-sm leading-relaxed text-ink-soft">
                    One free score to get started. Inspect the API shape with no payment, no headers and no rail to choose. Every other match is pay-per-view.
                </p>
                <div class="mt-3 font-mono text-xs text-ink-faint">GET /api/v1/scores/trial</div>
            </div>
            <div class="relative mt-5 w-full min-w-0 md:mt-0 md:w-[30rem]">
                <pre id="cmd-free" class="codeblock overflow-x-auto rounded-lg p-4 pr-16"><code>curl {{ $base }}/api/v1/scores/trial</code></pre>
            </div>
        </article>

        {{-- THE TWO PAID RAILS, side by side --}}
        <div class="mt-6 grid gap-6 lg:grid-cols-2">

            {{-- ── TEMPO RAIL ── --}}
            <div class="panel flex min-w-0 flex-col rounded-xl p-6 ring-1 ring-turf/15">
                <div class="flex items-center justify-between border-b pb-4" style="border-color: var(--color-line)">
                    <div>
                        <span class="eyebrow">Tempo rail</span>
                        <h3 class="mt-1.5 font-display text-xl font-700 text-ink">On-chain · pathUSD</h3>
                    </div>
                    <span class="cursor-help rounded-full border px-2.5 py-0.5 font-mono text-[11px] text-turf" style="border-color: var(--color-turf-tint); background: var(--color-turf-tint)" title="mppx dialect - the 402 carries a base64url request blob that a stock npx mppx agent reads. (Stripe uses the native accepts[] dialect instead.)">mppx dialect</span>
                </div>

                {{-- pay-per-view --}}
                <div class="mt-5">
                    <div class="flex items-center justify-between">
                        <span class="font-display text-sm font-700 text-ink">A specific match</span>
                        <span class="scorechip led text-sm">{{ $price['match'] }}</span>
                    </div>
                    <p class="mt-1.5 text-sm leading-relaxed text-ink-soft">
                        One result by id. <span class="font-600 text-ink">{{ $price['match'] }} {{ $price['currency'] }}</span> per request, settled on-chain.
                    </p>
                    <div class="mt-3 font-mono text-xs text-ink-faint">GET /api/v1/tempo/scores/match/{id}</div>
                    <div class="relative mt-3 w-full min-w-0">
                        <pre id="cmd-t-ppv" class="codeblock overflow-x-auto rounded-lg p-4"><code>npx mppx {{ $base }}/api/v1/tempo/scores/match/1 \
  --network testnet</code></pre>
                    </div>
                </div>

                {{-- decade pass --}}
                <div class="mt-6 border-t pt-5" style="border-color: var(--color-line)">
                    <div class="flex items-center justify-between">
                        <span class="font-display text-sm font-700 text-ink">Decade Pass · grants 3</span>
                        <span class="scorechip led text-sm">{{ $price['classics'] }}</span>
                    </div>
                    <p class="mt-1.5 text-sm leading-relaxed text-ink-soft">
                        One payment of <span class="font-600 text-ink">{{ $price['classics'] }} {{ $price['currency'] }}</span> unlocks the 80s, 90s and 00s - three calls on a reusable session.
                    </p>
                    <div class="mt-3 font-mono text-xs text-ink-faint">GET /api/v1/tempo/scores/classics/{80s|90s|00s}</div>
                    <div class="relative mt-3 w-full min-w-0">
                        <pre id="cmd-t-pass" class="codeblock overflow-x-auto rounded-lg p-4"><code>npx mppx {{ $base }}/api/v1/tempo/scores/classics/80s \
  --network testnet</code></pre>
                    </div>
                </div>
            </div>

            {{-- ── STRIPE RAIL ── --}}
            <div class="panel flex min-w-0 flex-col rounded-xl p-6">
                <div class="flex items-center justify-between border-b pb-4" style="border-color: var(--color-line)">
                    <div>
                        <span class="eyebrow">Stripe rail</span>
                        <h3 class="mt-1.5 font-display text-xl font-700 text-ink">Cards · Shared Payment Tokens</h3>
                    </div>
                    <span class="cursor-help rounded-full border px-2.5 py-0.5 font-mono text-[11px] text-turf" style="border-color: var(--color-turf-tint); background: var(--color-turf-tint)" title="Native MPP dialect - the 402 lists payment options as a signed accepts[] array. (Tempo uses the mppx dialect instead.)">native dialect</span>
                </div>

                {{-- pay-per-view --}}
                <div class="mt-5">
                    <div class="flex items-center justify-between">
                        <span class="font-display text-sm font-700 text-ink">A specific match</span>
                        <span class="scorechip led text-sm">${{ $stripe['match'] }}</span>
                    </div>
                    <p class="mt-1.5 text-sm leading-relaxed text-ink-soft">
                        Same result by id. <span class="font-600 text-ink">${{ $stripe['match'] }} {{ $stripe['currency'] }}</span> per request - priced to clear Stripe's card minimum.
                    </p>
                    <div class="mt-3 font-mono text-xs text-ink-faint">GET /api/v1/stripe/scores/match/{id}</div>
                    <div class="relative mt-3 w-full min-w-0">
                        <pre id="cmd-s-ppv" class="codeblock overflow-x-auto rounded-lg p-4"><code># native MPP 402 - an SPT-capable agent
# presents a Shared Payment Token and retries
curl {{ $base }}/api/v1/stripe/scores/match/1</code></pre>
                    </div>
                </div>

                {{-- decade pass --}}
                <div class="mt-6 border-t pt-5" style="border-color: var(--color-line)">
                    <div class="flex items-center justify-between">
                        <span class="font-display text-sm font-700 text-ink">Decade Pass · grants 3</span>
                        <span class="scorechip led text-sm">${{ $stripe['classics'] }}</span>
                    </div>
                    <p class="mt-1.5 text-sm leading-relaxed text-ink-soft">
                        One payment of <span class="font-600 text-ink">${{ $stripe['classics'] }} {{ $stripe['currency'] }}</span> unlocks all three decades - same metered session, settled on a PaymentIntent.
                    </p>
                    <div class="mt-3 font-mono text-xs text-ink-faint">GET /api/v1/stripe/scores/classics/{80s|90s|00s}</div>
                    <div class="relative mt-3 w-full min-w-0">
                        <pre id="cmd-s-pass" class="codeblock overflow-x-auto rounded-lg p-4"><code>curl {{ $base }}/api/v1/stripe/scores/classics/80s</code></pre>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sample response --}}
        <div class="mt-8 panel rounded-xl p-6">
            <span class="eyebrow">What comes back · once you've paid</span>
            <p class="mt-3 max-w-2xl text-sm leading-relaxed text-ink-soft">
                The first paid request returns a <span class="scorechip led text-xs">402</span>.
                After the client settles and retries, the API serves the scoreline with a payment receipt.
            </p>
            <pre class="codeblock mt-4 overflow-x-auto rounded-lg p-4"><code>{
  "tier": "pay-per-view",
  "scoreline": {
    "id": 1,
    "home_score": 7,
    "away_score": 1,
    "year": 2014,
    "teams": null
  }
}</code></pre>
        </div>
    </div>
</section>

{{-- ============================== PREMIUM TEASER ============================== --}}
<section id="premium" class="relative overflow-hidden border-b bg-surface-2" style="border-color: var(--color-line)">
    <div class="relative mx-auto max-w-6xl px-5 py-20 md:py-24">
        <div class="grid items-center gap-10 md:grid-cols-2">
            <div>
                <p class="eyebrow">Premium · roadmap</p>
                <h2 class="mt-4 font-display text-3xl font-800 tracking-tight text-ink md:text-5xl">
                    Team names.<br><span class="text-turf-bright">Coming soon.</span>
                </h2>
                <p class="mt-5 leading-relaxed text-ink-soft">
                    Right now a {{ $brand }} result tells you it finished <span class="scorechip led text-sm">7–1</span>
                    and trusts your football memory to do the rest.
                </p>
                <p class="mt-4 leading-relaxed text-ink-soft">
                    For those who insist on labels, the Premium tier will attach team names to every
                    scoreline. Pricing to be announced, probably after extra time.
                </p>
            </div>
            {{-- A dark scoreboard "device" so the big score still glows. --}}
            <div class="board rounded-xl p-7">
                <div class="flex items-center justify-between border-b pb-4" style="border-color: var(--color-board-line)">
                    <span class="font-mono text-xs" style="color: #6f7d77">scoreline #1</span>
                    <span class="rounded-full border px-2.5 py-0.5 font-mono text-[11px]" style="border-color: var(--color-board-line); color: var(--color-amber-dim)">premium</span>
                </div>
                <div class="mt-6 text-center">
                    <div class="led text-6xl md:text-7xl">7–1</div>
                </div>
                <div class="mt-8 space-y-3 font-mono text-sm">
                    <div class="flex items-center justify-between">
                        <span style="color: #8a958f">home</span>
                        <span class="rounded px-8 py-1 blur-[3px] select-none" style="background: rgba(36,49,64,0.6); color: #6f7d77" aria-hidden="true">████████</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span style="color: #8a958f">away</span>
                        <span class="rounded px-8 py-1 blur-[3px] select-none" style="background: rgba(36,49,64,0.6); color: #6f7d77" aria-hidden="true">██████</span>
                    </div>
                </div>
                <p class="mt-6 text-center font-mono text-xs" style="color: #6f7d77">unlock with Premium · soon™</p>
            </div>
        </div>
    </div>
</section>

{{-- ============================== THE TURN: IS THIS REAL? ============================== --}}
<section id="real" class="border-b bg-surface-2" style="border-color: var(--color-line)">
    <div class="mx-auto max-w-6xl px-5 py-20 md:py-28">
        <p class="eyebrow">Levelling with you</p>
        <h2 class="mt-4 max-w-3xl font-display text-3xl font-800 tracking-tight text-ink md:text-5xl">
            Wait - is this for real?
        </h2>
        <div class="mt-6 grid gap-10 md:grid-cols-[1.1fr_0.9fr]">
            <div class="space-y-4 leading-relaxed text-ink-soft">
                <p>
                    Yes, in the way a score API without teams can be real. {{ $brand }} is a
                    working demo of the <strong class="font-700 text-ink">Machine Payments Protocol (MPP)</strong>, built by
                    <a href="{{ $links['square1'] }}" class="font-600 text-turf-bright underline-offset-4 hover:underline">Square1</a>
                    on our open-source package
                    <a href="{{ $links['package'] }}" class="font-600 text-turf-bright underline-offset-4 hover:underline">square1/laravel-mpp</a>.
                </p>
                <p>
                    MPP lets a server charge for a request using nothing but HTTP. There's no signup, no
                    checkout page, no stored card. The buyer is usually a <em class="not-italic font-600 text-ink">software agent</em>,
                    and it pays per request, in the moment, then moves on.
                </p>
                <p>
                    Those paid {{ $brand }} endpoints above are gated by exactly this package. The same
                    402 loop runs on two rails: a pathUSD transfer on Tempo's test network, or a Stripe
                    PaymentIntent confirmed from a Shared Payment Token.
                </p>
            </div>

            {{-- The 402 loop, drawn as a sequence --}}
            <div class="panel min-w-0 rounded-xl p-6">
                <span class="eyebrow">The loop</span>
                <ol class="mt-5 space-y-5">
                    <li class="flex gap-4">
                        <span class="scorechip led shrink-0 text-sm">402</span>
                        <div>
                            <div class="font-display font-700 text-ink">Payment Required</div>
                            <p class="mt-1 text-sm text-ink-soft">The unpaid request comes back with a signed challenge: amount, method, scope and expiry.</p>
                        </div>
                    </li>
                    <li class="flex gap-4">
                        <span class="scorechip led shrink-0 text-sm">pay</span>
                        <div>
                            <div class="font-display font-700 text-ink">Settle &amp; retry</div>
                            <p class="mt-1 text-sm text-ink-soft">The agent settles on its rail, then retries the same request with a payment credential.</p>
                        </div>
                    </li>
                    <li class="flex gap-4">
                        <span class="scorechip led shrink-0 text-sm">200</span>
                        <div>
                            <div class="font-display font-700 text-ink">Resource + receipt</div>
                            <p class="mt-1 text-sm text-ink-soft">The server verifies settlement, serves the data, and returns a receipt with the settlement reference.</p>
                        </div>
                    </li>
                </ol>
            </div>
        </div>
    </div>
</section>

{{-- ============================== GETTING STARTED (TABBED) ============================== --}}
<section id="start" class="border-b" style="border-color: var(--color-line)">
    <div class="mx-auto max-w-6xl px-5 py-20 md:py-28">
        <p class="eyebrow">Getting started</p>
        <h2 class="mt-4 max-w-3xl font-display text-3xl font-800 tracking-tight text-ink md:text-5xl">
            Get your classic goals today!
        </h2>
        <p class="mt-5 max-w-2xl leading-relaxed text-ink-soft">
            Pick a settlement rail. Tempo runs fully end to end with the stock <code class="font-mono font-600 text-turf">npx mppx</code>
            client. Stripe returns the same kind of paid resource after the buyer presents an SPT through Link or a test token.
        </p>

        <div data-tabs class="mt-10">
            {{-- Tablist --}}
            <div role="tablist" aria-label="Settlement rail" class="inline-flex gap-1 rounded-lg border bg-surface p-1 font-display text-sm font-700" style="border-color: var(--color-line)">
                <button role="tab" id="tab-tempo" class="tab rounded-md px-4 py-2 transition" aria-selected="true" aria-controls="panel-tempo">
                    Tempo
                </button>
                <button role="tab" id="tab-stripe" class="tab rounded-md px-4 py-2 transition" aria-selected="false" aria-controls="panel-stripe" tabindex="-1">
                    Stripe
                </button>
            </div>

            {{-- TEMPO PANEL --}}
            <div role="tabpanel" id="panel-tempo" aria-labelledby="tab-tempo" class="mt-8">
                <div class="grid gap-5 lg:grid-cols-2">
                    {{-- Step 1: wallet --}}
                    <div class="panel min-w-0 rounded-xl p-6">
                        <span class="eyebrow">Step 1 · Wallet</span>
                        <h3 class="mt-3 font-display text-lg font-700 text-ink">Create &amp; fund a testnet account</h3>
                        <p class="mt-2 text-sm text-ink-soft">A throwaway wallet, funded with test pathUSD. No real money touches this.</p>
                        <pre id="t-step1" class="codeblock mt-4 overflow-x-auto rounded-lg p-4"><code>npx mppx account create
npx mppx account fund --network testnet</code></pre>
                    </div>

                    {{-- Step 2: call & 402 --}}
                    <div class="panel min-w-0 rounded-xl p-6">
                        <span class="eyebrow">Step 2 · Call it</span>
                        <h3 class="mt-3 font-display text-lg font-700 text-ink">Hit a paid endpoint</h3>
                        <p class="mt-2 text-sm text-ink-soft">mppx fetches the <span class="scorechip led text-xs">402</span>, signs the transfer, and retries - all in one command.</p>
                        <pre id="t-step2" class="codeblock mt-4 overflow-x-auto rounded-lg p-4"><code>npx mppx {{ $base }}/api/v1/tempo/scores/match/1 \
  --network testnet --account main</code></pre>
                    </div>
                </div>

                {{-- The real exchange --}}
                <div class="mt-5 grid gap-5 lg:grid-cols-2">
                    {{-- 402 --}}
                    <div class="panel min-w-0 rounded-xl p-6">
                        <div class="flex items-center justify-between">
                            <span class="eyebrow">Unpaid → <span class="scorechip led text-xs">402</span></span>
                            <span class="font-mono text-[11px] text-ink-faint">application/problem+json</span>
                        </div>
                        <pre class="codeblock mt-4 overflow-x-auto rounded-lg p-4"><code>HTTP/1.1 402 Payment Required
WWW-Authenticate: Payment id="LRt7…w7k",
  realm="{{ parse_url($base, PHP_URL_HOST) ?: 'localhost' }}", method="tempo",
  intent="charge", request="&lt;base64&gt;",
  expires="2026-06-23T12:57:10.224Z"

{
  "type": "https://paymentauth.org/problems/payment-required",
  "title": "Payment Required",
  "status": 402,
  "detail": "Payment is required.",
  "challengeId": "LRt7…w7k"
}</code></pre>
                        <p class="mt-3 break-words font-mono text-xs leading-relaxed text-ink-faint">
                            request decodes to →
                            <span class="text-ink-soft">{"amount":"10000","currency":"{{ \Illuminate\Support\Str::limit($tempo['token'], 10, '…') }}","methodDetails":{"chainId":{{ $tempo['chain_id'] }}},"recipient":"{{ \Illuminate\Support\Str::limit($tempo['recipient'], 8, '…') }}"}</span>
                            <br>10000 = {{ $price['match'] }} pathUSD at {{ $tempo['decimals'] }} decimals.
                        </p>
                    </div>

                    {{-- 200 + receipt --}}
                    <div class="panel min-w-0 rounded-xl p-6">
                        <div class="flex items-center justify-between">
                            <span class="eyebrow">Paid → <span class="scorechip led text-xs">200</span></span>
                            <span class="font-mono text-[11px] text-ink-faint">on-chain settled</span>
                        </div>
                        <pre class="codeblock mt-4 overflow-x-auto rounded-lg p-4"><code>HTTP/1.1 200 OK
Payment-Receipt: &lt;base64url-json&gt;

# decoded receipt:
{
  "method": "tempo",
  "status": "success",
  "timestamp": "2026-06-23T12:51:42.163Z",
  "reference": "0x3da1…913b"
}</code></pre>
                        <p class="mt-3 break-words font-mono text-xs leading-relaxed text-ink-faint">
                            <span class="text-ink-soft">reference</span> is the settled transaction hash. The funds - {{ $price['match'] }} pathUSD -
                            land at the recipient on Tempo {{ $tempo['network'] }}.
                            <a href="{{ $tempoExplorerAddress }}" class="text-turf-bright underline-offset-4 hover:underline" target="_blank" rel="noreferrer">View the recipient wallet</a>.
                        </p>
                    </div>
                </div>

                {{-- Decade pass / session reuse --}}
                <div class="mt-5 panel rounded-xl p-6">
                    <span class="eyebrow">Decade Pass · pay once, reuse the session</span>
                    <h3 class="mt-3 font-display text-lg font-700 text-ink">One payment, three decades</h3>
                    <p class="mt-2 max-w-3xl text-sm text-ink-soft">
                        A metered endpoint charges once and hands back a <code class="font-mono font-600 text-turf">Payment-Session</code>
                        with credits. Present that session on the sibling endpoints and they're served with no
                        new payment, until the credits run out.
                    </p>
                    <div class="mt-5 grid gap-5 lg:grid-cols-2">
                        <pre class="codeblock min-w-0 overflow-x-auto rounded-lg p-4"><code># 1 · pay once - issues a 3-credit session
npx mppx {{ $base }}/api/v1/tempo/scores/classics/80s \
  --network testnet --account main -i

→ Payment-Session: id="sess_…EP",
    remaining="2", scope="tempo.classics"</code></pre>
                        <pre class="codeblock min-w-0 overflow-x-auto rounded-lg p-4"><code># 2 · reuse it - no charge, credits decrement
curl {{ $base }}/api/v1/tempo/scores/classics/90s \
  -H 'Authorization: Payment session="sess_…EP"'

→ 200 OK · Payment-Session remaining="1"

curl {{ $base }}/api/v1/tempo/scores/classics/00s \
  -H 'Authorization: Payment session="sess_…EP"'

→ 200 OK · Payment-Session remaining="0"</code></pre>
                    </div>
                </div>
            </div>

            {{-- STRIPE PANEL --}}
            <div role="tabpanel" id="panel-stripe" aria-labelledby="tab-stripe" class="mt-8" hidden>

                <div class="panel rounded-xl p-6" style="background: var(--color-turf-tint); border-color: var(--color-turf-tint)">
                    <span class="eyebrow">Stripe rail</span>
                    <h3 class="mt-3 font-display text-lg font-700 text-ink">Two ways to satisfy the same Stripe challenge</h3>
                    <p class="mt-2 max-w-3xl text-sm leading-relaxed text-ink-soft">
                        A Stripe endpoint returns a native MPP <code class="font-mono font-600 text-turf">accepts[]</code>
                        challenge. From the client side, you can pay it with Stripe Link, or mint a test Shared
                        Payment Token yourself and replay the request.
                    </p>
                </div>

                <div class="mt-5 panel min-w-0 rounded-xl p-6">
                    <span class="eyebrow">Step 1 · Get the challenge</span>
                    <h3 class="mt-3 font-display text-lg font-700 text-ink">Hit a Stripe-rail endpoint</h3>
                    <p class="mt-2 max-w-3xl text-sm text-ink-soft">
                        Copy the <code class="font-mono font-600 text-turf">challengeId</code> and
                        <code class="font-mono font-600 text-turf">sig</code> from the response. The Stripe
                        accept also includes the seller's <code class="font-mono font-600 text-turf">network_id</code>
                        for Link wallets.
                    </p>
                    <pre id="s-step1" class="codeblock mt-4 overflow-x-auto rounded-lg p-4"><code>curl -i {{ $base }}/api/v1/stripe/scores/match/1</code></pre>
                    <pre class="codeblock mt-4 overflow-x-auto rounded-lg p-4"><code>HTTP/1.1 402 Payment Required
Content-Type: application/json

{
  "challengeId": "chal_...",
  "accepts": [
    {
      "method": "stripe",
      "amount": "{{ $stripe['match'] }}",
      "currency": "{{ $stripe['currency'] }}",
      "network_id": "profile_...",
      "sig": "..."
    }
  ]
}</code></pre>
                </div>

                {{-- Stripe payment options --}}
                <div class="mt-5 grid gap-5 lg:grid-cols-2">
                    {{-- Link --}}
                    <div class="panel min-w-0 rounded-xl p-6">
                        <div class="flex items-center justify-between">
                            <span class="eyebrow">Option A · Stripe Link</span>
                            <span class="rounded-full border px-2.5 py-0.5 font-mono text-[11px] text-turf" style="border-color: var(--color-turf-tint); background: var(--color-turf-tint)">US-gated</span>
                        </div>
                        <h3 class="mt-3 font-display text-lg font-700 text-ink">Approve in Link, retry with the SPT</h3>
                        <p class="mt-2 text-sm leading-relaxed text-ink-soft">
                            Link is the production buyer wallet for Stripe SPTs. It currently requires a US Link account.
                            The buyer approves the spend, then the wallet presents the token to this API.
                        </p>
                        <pre id="s-link" class="codeblock mt-4 overflow-x-auto rounded-lg p-4"><code>link-cli auth login
link-cli spend-request create \
  --network-id profile_... \
  --amount 100 \
  --credential-type shared_payment_token

link-cli mpp pay {{ $base }}/api/v1/stripe/scores/match/1 \
  --spend-request-id lsrq_...</code></pre>
                    </div>

                    {{-- Self-minted test SPT --}}
                    <div class="panel min-w-0 rounded-xl p-6">
                        <div class="flex items-center justify-between">
                            <span class="eyebrow">Option B · Test SPT</span>
                            <span class="rounded-full border px-2.5 py-0.5 font-mono text-[11px] text-turf" style="border-color: var(--color-turf-tint); background: var(--color-turf-tint)">no Link account</span>
                        </div>
                        <h3 class="mt-3 font-display text-lg font-700 text-ink">Mint a buyer token and replay</h3>
                        <p class="mt-2 text-sm leading-relaxed text-ink-soft">
                            For a quick test, mint an SPT from a buyer Stripe test account, then echo the challenge
                            signature back with the token. This exercises the same payment retry shape.
                        </p>
                        <pre id="s-spt" class="codeblock mt-4 overflow-x-auto rounded-lg p-4"><code>curl -s -u "sk_test_buyer_...:" \
  -H "Stripe-Version: 2026-05-27.preview" \
  -X POST https://api.stripe.com/v1/test_helpers/shared_payment/granted_tokens \
  -d payment_method=pm_card_visa \
  -d "usage_limits[currency]=usd" \
  -d "usage_limits[max_amount]=100"

curl -i {{ $base }}/api/v1/stripe/scores/match/1 \
  -H 'Authorization: Payment method="stripe", challengeId="chal_...", sig="...", spt="spt_..."'</code></pre>
                    </div>
                </div>

                <div class="mt-5 panel rounded-xl p-6">
                    <span class="eyebrow">Paid response</span>
                    <h3 class="mt-3 font-display text-lg font-700 text-ink">The API returns the scoreline and a receipt</h3>
                    <pre class="codeblock mt-4 overflow-x-auto rounded-lg p-4"><code>HTTP/1.1 200 OK
Payment-Receipt: id="rcpt_...", method="stripe",
  amount="{{ $stripe['match'] }}", currency="{{ $stripe['currency'] }}",
  ref="pi_3Q...", settledAt="..."

{
  "tier": "pay-per-view",
  "scoreline": { "id": 1, "home_score": 7, "away_score": 1, "teams": null }
}</code></pre>
                </div>

                {{-- Decade pass note --}}
                <div class="mt-5 panel rounded-xl p-6">
                    <span class="eyebrow">Decade Pass · same metering, on cards</span>
                    <h3 class="mt-3 font-display text-lg font-700 text-ink">One ${{ $stripe['classics'] }} charge, three decades</h3>
                    <p class="mt-2 max-w-3xl text-sm text-ink-soft">
                        The <code class="font-mono font-600 text-turf">/api/v1/stripe/scores/classics/{80s|90s|00s}</code> endpoint
                        settles a single ${{ $stripe['classics'] }} {{ $stripe['currency'] }} PaymentIntent and issues a 3-credit
                        <code class="font-mono font-600 text-turf">Payment-Session</code> - the same metered session as Tempo, paid by card instead of on-chain.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

</main>

{{-- ============================== FOOTER ============================== --}}
<footer class="border-t bg-surface-2" style="border-color: var(--color-line)">
    <div class="mx-auto max-w-6xl px-5 py-14">
        <div class="flex flex-col justify-between gap-8 md:flex-row md:items-end">
            <div>
                <div class="flex items-center gap-2.5">
                    <span class="scorechip led text-base">{{ mb_substr($brand, 0, 1) }}–</span>
                    <span class="font-display text-lg font-700 text-ink">{{ $brand }}</span>
                </div>
                <p class="mt-3 max-w-md text-sm leading-relaxed text-ink-soft">
                    {{ $brand }} is a demo by <a href="{{ $links['square1'] }}" class="font-600 text-turf-bright underline-offset-4 hover:underline">Square1</a>,
                    showing the <a href="{{ $links['package'] }}" class="font-600 text-turf-bright underline-offset-4 hover:underline">square1/laravel-mpp</a>
                    package in action. The scorelines are real; the team names are a premium feature, due any day now.
                    Two settlement rails: Tempo testnet pathUSD, or Stripe test-mode cards.
                </p>
            </div>
            <nav class="flex flex-col gap-2 font-mono text-sm text-ink-soft md:text-right">
                <a href="{{ $links['square1'] }}" class="transition hover:text-turf-bright">square1.io →</a>
                <a href="{{ $links['package'] }}" class="transition hover:text-turf-bright">github · laravel-mpp →</a>
                <a href="{{ $links['mpp'] }}" class="transition hover:text-turf-bright">mpp.dev →</a>
                <a href="{{ $tempoExplorerAddress }}" class="transition hover:text-turf-bright" target="_blank" rel="noreferrer">Tempo recipient →</a>
            </nav>
        </div>
        <div class="mt-10 border-t pt-6 font-mono text-xs text-ink-faint" style="border-color: var(--color-line)">
            © {{ date('Y') }} Square1 · Built on the Machine Payments Protocol · Tempo {{ $tempo['network'] }} pathUSD · Stripe Shared Payment Tokens
        </div>
    </div>
</footer>

</body>
</html>
