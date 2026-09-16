<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fati Market &middot; Student Marketplace</title>
    <meta name="description" content="Fati Market is an independent student marketplace for buying, selling, and arranging campus pickup.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --brand-900:#0C3021; --brand-800:#10432D; --brand-600:#1A6E49; --brand-500:#22885B; --brand-100:#DCEFE4; --ink-900:#101513; --ink-700:#33403A; --ink-500:#6B7A72; --canvas:#F2F5F3; --surface:#fff; --line:#E4E9E6; --radius:14px; }
        * { box-sizing:border-box; }
        body { margin:0; font-family:Inter,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif; color:var(--ink-700); background:var(--canvas); line-height:1.6; -webkit-font-smoothing:antialiased; }
        a { color:inherit; } a:focus-visible { outline:2px solid var(--brand-500); outline-offset:3px; border-radius:4px; }
        .shell { width:min(1120px, calc(100% - 48px)); margin:auto; }
        header { background:var(--brand-900); color:#fff; }
        nav { height:76px; display:flex; align-items:center; justify-content:space-between; gap:24px; }
        .brand { text-decoration:none; font-size:18px; font-weight:800; letter-spacing:-.03em; }
        .brand span { color:#A6D9B8; }
        .nav-links { display:flex; align-items:center; gap:20px; font-size:14px; }
        .nav-links a { color:rgba(255,255,255,.82); text-decoration:none; }.nav-links a:hover { color:#fff; }
        .hero { display:grid; grid-template-columns:1.2fr .8fr; gap:64px; align-items:center; padding:68px 0 90px; }
        .eyebrow { margin:0 0 16px; color:#A6D9B8; font-size:12px; font-weight:700; letter-spacing:.12em; text-transform:uppercase; }
        h1 { margin:0; max-width:730px; font-size:clamp(38px,6vw,66px); line-height:1.04; letter-spacing:-.055em; }
        .hero-copy { max-width:620px; margin:22px 0 0; color:rgba(255,255,255,.8); font-size:18px; }
        .actions { display:flex; flex-wrap:wrap; gap:12px; margin-top:30px; }
        .button { display:inline-flex; align-items:center; justify-content:center; min-height:46px; padding:0 20px; border-radius:9px; font-size:14px; font-weight:700; text-decoration:none; }
        .button-primary { background:#fff; color:var(--brand-800); }.button-primary:hover { background:var(--brand-100); }
        .button-secondary { border:1px solid rgba(255,255,255,.32); color:#fff; }.button-secondary:hover { background:rgba(255,255,255,.1); }
        .hero-card { position:relative; padding:28px; border:1px solid rgba(255,255,255,.16); border-radius:var(--radius); background:linear-gradient(145deg,rgba(255,255,255,.14),rgba(255,255,255,.04)); box-shadow:0 22px 52px rgba(0,0,0,.15); }
        .hero-card p { margin:0; }.card-label { color:#A6D9B8; font-size:12px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; }.card-title { margin-top:12px!important; color:#fff; font-size:22px; font-weight:700; line-height:1.25; }.steps { display:grid; gap:13px; margin-top:24px; }.step { display:flex; align-items:center; gap:12px; color:rgba(255,255,255,.86); font-size:14px; }.number { display:grid; place-items:center; width:26px; height:26px; flex:0 0 26px; border-radius:50%; background:#A6D9B8; color:var(--brand-900); font-size:12px; font-weight:800; }
        main { padding:78px 0; }.section-head { max-width:650px; }.section-head h2 { margin:0; color:var(--ink-900); font-size:clamp(28px,4vw,38px); line-height:1.15; letter-spacing:-.04em; }.section-head p { margin:14px 0 0; color:var(--ink-500); }
        .features { display:grid; grid-template-columns:repeat(3,1fr); gap:18px; margin-top:34px; }.feature { padding:24px; background:var(--surface); border:1px solid var(--line); border-radius:var(--radius); }.icon { display:grid; place-items:center; width:38px; height:38px; border-radius:10px; background:var(--brand-100); color:var(--brand-800); font-weight:800; }.feature h3 { margin:18px 0 8px; color:var(--ink-900); font-size:16px; }.feature p { margin:0; font-size:14px; color:var(--ink-500); }
        .notice { margin-top:62px; padding:28px 32px; display:flex; justify-content:space-between; align-items:center; gap:24px; background:var(--brand-100); border-left:4px solid var(--brand-500); border-radius:0 var(--radius) var(--radius) 0; }.notice h2 { margin:0; color:var(--brand-900); font-size:19px; }.notice p { margin:5px 0 0; font-size:14px; color:var(--brand-800); }.notice a { color:var(--brand-800); font-weight:700; }
        footer { border-top:1px solid var(--line); background:#fff; padding:28px 0; font-size:13px; color:var(--ink-500); }.footer-inner { display:flex; align-items:center; justify-content:space-between; gap:16px; }.footer-links { display:flex; gap:18px; }.footer-links a { color:var(--ink-700); text-decoration:none; }.footer-links a:hover { color:var(--brand-600); }
        @media (max-width:760px) { .shell { width:min(100% - 36px,1120px); }.nav-links { gap:13px; font-size:13px; }.hero { grid-template-columns:1fr; gap:34px; padding:48px 0 62px; }.hero-card { max-width:470px; }.features { grid-template-columns:1fr; }.notice,.footer-inner { align-items:flex-start; flex-direction:column; }.notice { margin-top:42px; padding:24px; } main { padding:54px 0; } }
    </style>
</head>
<body>
    <header>
        <nav class="shell" aria-label="Main navigation">
            <a class="brand" href="{{ route('home') }}">Fati <span>Market</span></a>
            <div class="nav-links">
                <a href="{{ route('privacy-policy') }}">Privacy</a>
                <a href="{{ route('terms-of-service') }}">Terms</a>
                <a href="{{ route('admin.login') }}">Admin</a>
            </div>
        </nav>
        <div class="shell hero">
            <div>
                <p class="eyebrow">Student marketplace</p>
                <h1>Give useful things a second life on campus.</h1>
                <p class="hero-copy">Fati Market helps students discover, buy, and offer pre-loved items in one simple, campus-focused marketplace.</p>
                <div class="actions">
                    <a class="button button-primary" href="{{ route('privacy-policy') }}">Read our privacy policy</a>
                    <a class="button button-secondary" href="{{ route('terms-of-service') }}">Terms of service</a>
                </div>
            </div>
            <aside class="hero-card" aria-label="How Fati Market works">
                <p class="card-label">How it works</p>
                <p class="card-title">A more practical way to buy and sell with fellow students.</p>
                <div class="steps">
                    <div class="step"><span class="number">1</span> Sign in with your student account</div>
                    <div class="step"><span class="number">2</span> Browse or offer available items</div>
                    <div class="step"><span class="number">3</span> Arrange payment and campus pickup</div>
                </div>
            </aside>
        </div>
    </header>
    <main class="shell">
        <section>
            <div class="section-head">
                <p class="eyebrow" style="color:var(--brand-600)">Made for students</p>
                <h2>Everything needed for a smoother campus marketplace.</h2>
                <p>Fati Market keeps the process straightforward—from browsing listings through pickup—while keeping account, order, and communication details organized.</p>
            </div>
            <div class="features">
                <article class="feature"><div class="icon">01</div><h3>Student-focused listings</h3><p>Find useful items from fellow students, with details and photos that help you decide quickly.</p></article>
                <article class="feature"><div class="icon">02</div><h3>Clear order updates</h3><p>Track the progress of your order and receive notifications when an update needs your attention.</p></article>
                <article class="feature"><div class="icon">03</div><h3>Campus pickup</h3><p>Use a pickup code to help ensure that the right order reaches the right student.</p></article>
            </div>
        </section>
        <section class="notice" aria-label="Independent project notice">
            <div><h2>An independent student project</h2><p>Fati Market is not owned, operated, sponsored, or endorsed by Our Lady of Fatima University.</p></div>
            <a href="{{ route('privacy-policy') }}#who-we-are">Learn more</a>
        </section>
    </main>
    <footer><div class="shell footer-inner"><span>&copy; {{ date('Y') }} Fati Market</span><div class="footer-links"><a href="{{ route('privacy-policy') }}">Privacy Policy</a><a href="{{ route('terms-of-service') }}">Terms of Service</a></div></div></footer>
</body>
</html>
