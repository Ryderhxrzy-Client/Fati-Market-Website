<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Cancelled &middot; Fati Market</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --brand-900: #0C3021;
            --brand-800: #10432D;
            --ink-900: #101513;
            --ink-700: #33403A;
            --ink-500: #6B7A72;
            --canvas: #F2F5F3;
            --surface: #ffffff;
            --line: #E4E9E6;
            --radius: 16px;
            --cancel-red: #EF4444;
            --cancel-bg: #FEF2F2;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: var(--ink-700);
            background: var(--canvas);
            line-height: 1.6;
            display: grid;
            min-height: 100vh;
            place-items: center;
            padding: 24px;
        }
        .card {
            width: min(460px, 100%);
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: var(--radius);
            padding: 40px 32px;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0,0,0,0.06);
        }
        .icon-box {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: var(--cancel-bg);
            color: var(--cancel-red);
            display: grid;
            place-items: center;
            margin: 0 auto 20px;
        }
        .icon-box svg {
            width: 40px;
            height: 40px;
            stroke-width: 2.5;
        }
        h1 {
            margin: 0 0 10px;
            color: var(--ink-900);
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -.03em;
        }
        p {
            margin: 0 0 24px;
            color: var(--ink-500);
            font-size: 15px;
        }
        .txn-badge {
            display: inline-block;
            background: #F3F4F6;
            color: var(--ink-700);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 28px;
        }
        .actions {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 48px;
            padding: 0 24px;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all 0.15s ease;
        }
        .btn-primary {
            background: var(--brand-800);
            color: #ffffff;
        }
        .btn-primary:hover {
            background: var(--brand-900);
        }
        .btn-secondary {
            background: var(--canvas);
            color: var(--ink-700);
            border: 1px solid var(--line);
        }
        .btn-secondary:hover {
            background: #E8ECE9;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon-box">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </div>
        <h1>Payment Cancelled</h1>
        <p>The online checkout process was not completed. No charges were made to your account.</p>
        
        @if(!empty($transaction_id))
            <div class="txn-badge">Order #{{ $transaction_id }}</div>
        @endif

        <div class="actions">
            <a href="fatimarket://payment-result?status=cancel&transaction_id={{ $transaction_id }}" class="btn btn-primary">
                Return to Mobile App
            </a>
            <button onclick="window.close()" class="btn btn-secondary">
                Close Window
            </button>
        </div>
    </div>
</body>
</html>

