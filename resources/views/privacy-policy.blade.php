<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy &middot; Fati Market</title>
    <meta name="description" content="How the Fati Market app collects, uses, and protects your personal information.">

    {{--
        Deliberately self-contained: no Vite bundle, no CDN framework. Google Play's
        reviewers fetch this URL directly and it has to render even when the
        asset build has not been run, so the styles live inline. The palette is
        lifted from layouts/admin-auth.blade.php so it still looks like us.
    --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --brand-900: #0C3021;
            --brand-800: #10432D;
            --brand-600: #1A6E49;
            --brand-500: #22885B;
            --brand-100: #DCEFE4;

            --ink-900: #101513;
            --ink-700: #33403A;
            --ink-500: #6B7A72;
            --ink-400: #94A29B;

            --surface: #FFFFFF;
            --canvas: #F2F5F3;
            --line: #E4E9E6;
            --radius: 12px;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--canvas);
            color: var(--ink-700);
            line-height: 1.65;
            -webkit-font-smoothing: antialiased;
        }

        a { color: var(--brand-600); }
        a:hover { color: var(--brand-800); }
        :focus-visible { outline: 2px solid var(--brand-500); outline-offset: 2px; border-radius: 4px; }

        .masthead {
            background: var(--brand-900);
            color: #fff;
            padding: 40px 24px 44px;
        }

        .masthead-inner, .page { max-width: 940px; margin: 0 auto; }

        .eyebrow {
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--brand-100);
            margin: 0 0 10px;
        }

        .masthead h1 {
            margin: 0 0 12px;
            font-size: clamp(28px, 5vw, 40px);
            line-height: 1.15;
            letter-spacing: -0.02em;
            color: #fff;
        }

        .masthead p {
            margin: 0;
            max-width: 62ch;
            color: rgba(255, 255, 255, 0.82);
        }

        .dates {
            margin-top: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px 10px;
            font-size: 13px;
        }

        .date-chip {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.16);
            border-radius: 999px;
            padding: 4px 12px;
            color: rgba(255, 255, 255, 0.9);
        }

        .page { padding: 0 24px 72px; }

        .layout {
            display: grid;
            grid-template-columns: 220px 1fr;
            gap: 40px;
            align-items: start;
        }

        nav.toc {
            position: sticky;
            top: 24px;
            margin-top: 32px;
            font-size: 13.5px;
        }

        nav.toc h2 {
            font-size: 11px;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--ink-500);
            margin: 0 0 10px;
        }

        nav.toc ol {
            list-style: none;
            margin: 0;
            padding: 0;
            counter-reset: toc;
        }

        nav.toc li { counter-increment: toc; margin-bottom: 2px; }

        nav.toc a {
            display: block;
            padding: 5px 10px;
            border-radius: 7px;
            text-decoration: none;
            color: var(--ink-700);
            border-left: 2px solid transparent;
        }

        nav.toc a::before {
            content: counter(toc) ".";
            color: var(--ink-400);
            margin-right: 7px;
            font-variant-numeric: tabular-nums;
        }

        nav.toc a:hover {
            background: var(--brand-100);
            border-left-color: var(--brand-500);
            color: var(--brand-800);
        }

        main {
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: var(--radius);
            padding: 8px 40px 40px;
            margin-top: -24px;
            box-shadow: 0 12px 32px -16px rgba(16, 21, 19, 0.18);
        }

        section { scroll-margin-top: 24px; padding-top: 28px; }

        h2 {
            font-size: 21px;
            letter-spacing: -0.015em;
            color: var(--ink-900);
            margin: 0 0 12px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--line);
        }

        h3 {
            font-size: 15px;
            color: var(--ink-900);
            margin: 22px 0 6px;
        }

        p, li { font-size: 15px; }
        ul { padding-left: 20px; }
        li { margin-bottom: 7px; }

        strong { color: var(--ink-900); font-weight: 600; }

        code {
            background: var(--canvas);
            border: 1px solid var(--line);
            border-radius: 5px;
            padding: 1px 5px;
            font-size: 13.5px;
        }

        .callout {
            background: var(--brand-100);
            border-left: 3px solid var(--brand-500);
            border-radius: 0 var(--radius) var(--radius) 0;
            padding: 14px 18px;
            margin: 20px 0;
        }

        .callout p { margin: 0; color: var(--brand-900); }

        .table-wrap { overflow-x: auto; margin: 16px 0; }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            min-width: 520px;
        }

        th, td {
            text-align: left;
            padding: 11px 14px;
            border-bottom: 1px solid var(--line);
            vertical-align: top;
        }

        th {
            background: var(--canvas);
            color: var(--ink-900);
            font-weight: 600;
            font-size: 12.5px;
            letter-spacing: 0.03em;
            text-transform: uppercase;
        }

        tbody tr:last-child td { border-bottom: none; }

        footer.legal {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid var(--line);
            font-size: 13.5px;
            color: var(--ink-500);
        }

        @media (max-width: 820px) {
            .layout { grid-template-columns: 1fr; gap: 0; }
            nav.toc { position: static; margin: 28px 0 0; }
            nav.toc ol { columns: 2; column-gap: 18px; }
            main { padding: 8px 22px 32px; }
        }

        @media print {
            body { background: #fff; }
            .masthead { background: #fff; color: #000; padding: 0 0 16px; }
            .masthead h1, .masthead p { color: #000; }
            .eyebrow, .date-chip { color: #444; background: none; border: none; }
            nav.toc { display: none; }
            .layout { grid-template-columns: 1fr; }
            main { border: none; box-shadow: none; margin: 0; padding: 0; }
            section { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <header class="masthead">
        <div class="masthead-inner">
            <p class="eyebrow">Fati Market</p>
            <h1>Privacy Policy</h1>
            <p>
                This policy explains what the Fati Market mobile app collects, why it collects it,
                who it is shared with, and how you can get it deleted. It describes what the app
                actually does, not every hypothetical.
            </p>
            <div class="dates">
                <span class="date-chip">Effective 2 September 2026</span>
                <span class="date-chip">Last updated 2 September 2026</span>
                <span class="date-chip">Applies to app version 1.0</span>
            </div>
        </div>
    </header>

    <div class="page">
        <div class="layout">
            <nav class="toc" aria-label="Sections">
                <h2>Contents</h2>
                <ol>
                    <li><a href="#who-we-are">Who we are</a></li>
                    <li><a href="#what-we-collect">What we collect</a></li>
                    <li><a href="#permissions">Device permissions</a></li>
                    <li><a href="#how-we-use">How we use it</a></li>
                    <li><a href="#payments">Payments</a></li>
                    <li><a href="#sharing">Who we share with</a></li>
                    <li><a href="#retention">How long we keep it</a></li>
                    <li><a href="#your-rights">Your rights</a></li>
                    <li><a href="#deletion">Deleting your account</a></li>
                    <li><a href="#children">Children</a></li>
                    <li><a href="#security">Security</a></li>
                    <li><a href="#changes">Changes</a></li>
                    <li><a href="#contact">Contact</a></li>
                </ol>
            </nav>

            <main>
                <section id="who-we-are">
                    <h2>1. Who we are</h2>
                    <p>
                        Fati Market is a student marketplace app where you can offer items for sale,
                        buy listed items, pay in cash or through GCash, earn loyalty points, and pick
                        up your orders on campus. In this policy, <strong>&ldquo;we&rdquo;</strong> means
                        the operator of Fati Market, who is responsible for your information.
                    </p>
                    <div class="callout">
                        <p>
                            <strong>Fati Market is an independent project.</strong> It is not owned,
                            operated, sponsored, or endorsed by Our Lady of Fatima University or any
                            other school. We use school email addresses only to confirm that you are a
                            student. The school does not receive your data from us.
                        </p>
                    </div>
                </section>

                <section id="what-we-collect">
                    <h2>2. What we collect</h2>
                    <p>We collect only what the app needs in order to work. Nothing here is used for advertising.</p>

                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th scope="col">Information</th>
                                    <th scope="col">Why we need it</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Your name</strong> &mdash; first and last</td>
                                    <td>To identify you to the admin when you place an order or offer an item.</td>
                                </tr>
                                <tr>
                                    <td><strong>Your school email address</strong></td>
                                    <td>This is your login, and how we confirm you are a student.</td>
                                </tr>
                                <tr>
                                    <td><strong>A personal email address</strong>, if you add one</td>
                                    <td>So you can still recover your account after you graduate and lose access to your school inbox. Optional.</td>
                                </tr>
                                <tr>
                                    <td><strong>Your password</strong></td>
                                    <td>Stored only as a scrambled (hashed) value. We cannot read it, and neither can anyone who obtains the database.</td>
                                </tr>
                                <tr>
                                    <td><strong>Google account name and email</strong>, if you use &ldquo;Continue with Google&rdquo;</td>
                                    <td>To create or sign you into your account. We receive a one-time sign-in token from Google. We never see or store your Google password.</td>
                                </tr>
                                <tr>
                                    <td><strong>Photos of items you list</strong></td>
                                    <td>So buyers can see what is for sale. Other users can see these.</td>
                                </tr>
                                <tr>
                                    <td><strong>Your orders and transactions</strong></td>
                                    <td>Items bought and sold, prices, order status, receipt numbers, and your loyalty point balance.</td>
                                </tr>
                                <tr>
                                    <td><strong>GCash reference number and payment screenshot</strong></td>
                                    <td>To manually confirm that a payment actually arrived. See <a href="#payments">section 5</a>.</td>
                                </tr>
                                <tr>
                                    <td><strong>Messages you send</strong></td>
                                    <td>Chat about a specific item goes to the Fati Market admin so they can answer you. Admins can read these.</td>
                                </tr>
                                <tr>
                                    <td><strong>Pickup codes</strong></td>
                                    <td>The QR code shown at pickup, so staff can confirm the right person collected the right order.</td>
                                </tr>
                                <tr>
                                    <td><strong>A notification token for your device</strong></td>
                                    <td>An identifier issued by Google that lets us send you order and chat notifications. It does not identify you personally.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <h3>What we do not collect</h3>
                    <p>
                        We do not collect your location, your contacts, your calendar, your call or SMS
                        history, files other than the photos you deliberately pick, or a list of the
                        other apps on your phone. The app contains no advertising and no analytics or
                        tracking software.
                    </p>
                </section>

                <section id="permissions">
                    <h2>3. Device permissions</h2>
                    <p>The app asks for these Android permissions, and only for these reasons:</p>
                    <ul>
                        <li>
                            <strong>Camera</strong> &mdash; to scan the pickup QR code and to take photos
                            of items you are listing. The camera runs only while a scanner or photo
                            screen is open. We do not record video or audio, and nothing is captured in
                            the background.
                        </li>
                        <li>
                            <strong>Notifications</strong> &mdash; to tell you when your order status
                            changes or when the admin replies to your message. You can turn these off in
                            your phone's settings and keep using the app.
                        </li>
                        <li>
                            <strong>Internet and network access</strong> &mdash; to communicate with our
                            server, and to tell you when you are offline.
                        </li>
                    </ul>
                    <p>You can withdraw any of these permissions at any time in your device settings.</p>
                </section>

                <section id="how-we-use">
                    <h2>4. How we use your information</h2>
                    <ul>
                        <li>To create your account and sign you in.</li>
                        <li>To show your listings to other students and process orders.</li>
                        <li>To confirm payment and hand over the right order at pickup.</li>
                        <li>To calculate and apply your loyalty points and discounts.</li>
                        <li>To let you and the admin message each other about an item.</li>
                        <li>To send you notifications about your own orders and messages.</li>
                        <li>To keep the records the admin needs to run the marketplace and settle disputes.</li>
                        <li>To investigate fraud, abuse, or misuse of the service.</li>
                    </ul>
                    <div class="callout">
                        <p>
                            <strong>We do not sell your personal information</strong>, rent it, trade it,
                            or share it with advertisers or data brokers. We do not use it to build
                            advertising profiles.
                        </p>
                    </div>
                </section>

                <section id="payments">
                    <h2>5. Payments</h2>
                    <p>
                        Fati Market does not process payments electronically and has no payment system
                        built into it. Cash is handled in person. GCash payments are made <em>outside</em>
                        the app, in the GCash app, and are then confirmed manually.
                    </p>
                    <p>
                        This means we never see and never store your card number, bank details, GCash
                        PIN, or GCash login. The only payment information that reaches us is the
                        reference number you type in and the screenshot you choose to upload as proof.
                        If you would rather keep the rest of your GCash activity private, crop the
                        screenshot so it shows only the transaction, not your balance or other transfers.
                    </p>
                </section>

                <section id="sharing">
                    <h2>6. Who we share information with</h2>

                    <h3>Fati Market administrators</h3>
                    <p>
                        Admins can see your name, school email, orders, payment proofs, and your
                        messages, because running the marketplace requires it.
                    </p>

                    <h3>Other users</h3>
                    <p>
                        Other students can see the items you list, including your photos and the name
                        attached to a listing. They cannot see your email address, your order history,
                        your points balance, or your messages.
                    </p>

                    <h3>Service providers</h3>
                    <ul>
                        <li>
                            <strong>Google</strong> &mdash; provides &ldquo;Continue with Google&rdquo;
                            sign-in and, through Firebase Cloud Messaging, delivers our notifications to
                            your device. Google's handling of that data is governed by the
                            <a href="https://policies.google.com/privacy" target="_blank" rel="noopener noreferrer">Google Privacy Policy</a>.
                        </li>
                        <li>
                            <strong>Our hosting provider</strong> &mdash; stores the app's database and
                            uploaded images on the server we operate at <code>fati-api.alertaraqc.com</code>.
                        </li>
                        <li>
                            <strong>A public emoji icon service</strong> &mdash; the app fetches category
                            icons from a third-party service. No personal information is sent, but like
                            any web request it does reveal your device's IP address to that service.
                        </li>
                    </ul>

                    <h3>Legal reasons</h3>
                    <p>
                        We may disclose information where we are legally required to, or where it is
                        necessary to protect someone's safety or our legal rights.
                    </p>
                </section>

                <section id="retention">
                    <h2>7. How long we keep your information</h2>
                    <ul>
                        <li><strong>Account details</strong> &mdash; for as long as your account exists.</li>
                        <li><strong>Order and transaction records</strong> &mdash; kept after an order finishes, because they are the marketplace's financial records and are needed to settle disputes.</li>
                        <li><strong>Payment proof screenshots</strong> &mdash; kept while the order can still be disputed, then deleted.</li>
                        <li><strong>Messages</strong> &mdash; kept for as long as the related item record exists.</li>
                        <li><strong>Notification tokens</strong> &mdash; removed when you sign out or uninstall the app.</li>
                    </ul>
                </section>

                <section id="your-rights">
                    <h2>8. Your rights</h2>
                    <p>
                        Under the Philippine <strong>Data Privacy Act of 2012 (Republic Act No. 10173)</strong>,
                        you have the right to:
                    </p>
                    <ul>
                        <li>Know what personal information we hold about you, and ask for a copy of it.</li>
                        <li>Correct anything that is wrong or out of date.</li>
                        <li>Object to how we use your information, or ask us to stop.</li>
                        <li>Have your information erased or blocked where the law allows it.</li>
                        <li>Complain to the National Privacy Commission if you believe your rights were violated.</li>
                    </ul>
                    <p>
                        To exercise any of these, email us at
                        <a href="mailto:admin@fatimarket.com">admin@fatimarket.com</a>.
                        We will respond within <strong>30 days</strong>. We may ask you to confirm your
                        identity first, so that nobody else can request your data.
                    </p>
                </section>

                <section id="deletion">
                    <h2>9. Deleting your account and data</h2>
                    <p>
                        You can ask us to delete your account at any time. Email
                        <a href="mailto:admin@fatimarket.com">admin@fatimarket.com</a>
                        from the email address on your account, with the subject line
                        <strong>&ldquo;Delete my account&rdquo;</strong>.
                    </p>
                    <p>Once we confirm the request:</p>
                    <ul>
                        <li>Your name, email addresses, password, listings, photos, messages, and notification token are <strong>permanently deleted within 30 days</strong>.</li>
                        <li>Completed order and payment records are <strong>kept but anonymised</strong>, so they can no longer be traced back to you. We keep these because they are the marketplace's financial records.</li>
                        <li>Any unused loyalty points are forfeited and cannot be restored.</li>
                    </ul>
                    <p>Deletion is permanent. We cannot bring an account back once it has been removed.</p>
                </section>

                <section id="children">
                    <h2>10. Children</h2>
                    <p>
                        Fati Market is intended for university students. It is not directed at children
                        under 13, and we do not knowingly collect information from them. If you believe a
                        child under 13 has given us personal information, contact us and we will delete
                        it. If you are under 18, please use the app with the knowledge and consent of a
                        parent or guardian.
                    </p>
                </section>

                <section id="security">
                    <h2>11. How we protect your information</h2>
                    <p>
                        Traffic between the app and our server is encrypted with HTTPS. Passwords are
                        stored only as hashes and are never readable, even by us. The admin console
                        requires a separate login, and admin sessions expire.
                    </p>
                    <p>
                        No system is perfectly secure, and we cannot promise absolute safety. If a breach
                        ever affects your personal information, we will notify you and the National
                        Privacy Commission as the Data Privacy Act requires.
                    </p>
                </section>

                <section id="changes">
                    <h2>12. Changes to this policy</h2>
                    <p>
                        We may update this policy as the app changes. The &ldquo;last updated&rdquo; date
                        at the top always reflects the current version. If a change materially affects
                        how we handle your information, we will tell you in the app or by email before it
                        takes effect. Continuing to use Fati Market after that means you accept the
                        updated policy.
                    </p>
                </section>

                <section id="contact">
                    <h2>13. Contact us</h2>
                    <p>For any question about this policy, your data, or a deletion request:</p>
                    <ul>
                        <li><strong>Email:</strong> <a href="mailto:admin@fatimarket.com">admin@fatimarket.com</a></li>
                        <li><strong>Subject line for data requests:</strong> &ldquo;Data request&rdquo; or &ldquo;Delete my account&rdquo;</li>
                        <li><strong>We reply within:</strong> 30 days</li>
                    </ul>
                    <p>
                        You may also raise a concern directly with the National Privacy Commission of the
                        Philippines at
                        <a href="https://www.privacy.gov.ph" target="_blank" rel="noopener noreferrer">privacy.gov.ph</a>.
                    </p>

                    <footer class="legal">
                        <p>
                            Fati Market is an independent student marketplace and is not affiliated with
                            or endorsed by Our Lady of Fatima University. Google and GCash are trademarks
                            of their respective owners and are referenced here only to describe the
                            services the app uses.
                        </p>
                    </footer>
                </section>
            </main>
        </div>
    </div>
</body>
</html>
