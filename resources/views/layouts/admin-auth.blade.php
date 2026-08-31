<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Sign in') · Fati Market Admin</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    {{--
        This page used to pull React, ReactDOM, Babel standalone and
        react-hot-toast from a CDN and then never touch any of them - about a
        megabyte of script in front of a two-field form. The toast below is the
        one the page actually calls.
    --}}
    <style>
        :root {
            --brand-900: #0C3021;
            --brand-800: #10432D;
            --brand-700: #14563A;
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
            --line-strong: #D3DAD6;
            --danger: #B3261E;
            --success: #17794A;

            --radius-sm: 8px;
            --radius: 12px;
            --shadow-lg: 0 12px 32px -12px rgba(16, 21, 19, 0.28);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--canvas);
            color: var(--ink-700);
            -webkit-font-smoothing: antialiased;
            margin: 0;
        }

        h1, h2, h3 { letter-spacing: -0.015em; color: var(--ink-900); }

        :focus-visible { outline: 2px solid var(--brand-500); outline-offset: 2px; border-radius: 4px; }

        .auth-input {
            width: 100%;
            padding: 12px 14px 12px 42px;
            border: 1px solid var(--line-strong);
            border-radius: var(--radius);
            font-size: 14.5px;
            font-family: inherit;
            color: var(--ink-900);
            background: var(--surface);
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }
        .auth-input:focus {
            outline: none;
            border-color: var(--brand-500);
            box-shadow: 0 0 0 3px var(--brand-100);
        }
        .auth-input.invalid { border-color: var(--danger); }
        .auth-input::placeholder { color: var(--ink-400); }

        .auth-icon {
            position: absolute; left: 15px; top: 50%; transform: translateY(-50%);
            color: var(--ink-400); font-size: 14px; pointer-events: none;
        }

        .auth-btn {
            width: 100%;
            height: 48px;
            border: none;
            border-radius: var(--radius);
            background: var(--brand-600);
            color: #fff;
            font-size: 15px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center; gap: 10px;
            transition: background-color 0.15s ease;
        }
        .auth-btn:hover:not(:disabled) { background: var(--brand-700); }
        .auth-btn:disabled { opacity: 0.7; cursor: progress; }

        #toast-container {
            position: fixed; top: 20px; right: 20px;
            z-index: 9999;
            display: flex; flex-direction: column; gap: 10px;
            align-items: flex-end;
        }

        .toast {
            display: flex; align-items: center; gap: 10px;
            padding: 12px 16px;
            min-width: 260px; max-width: 360px;
            border-radius: var(--radius);
            background: var(--surface);
            border: 1px solid var(--line);
            box-shadow: var(--shadow-lg);
            font-size: 13.5px;
            color: var(--ink-700);
            animation: toastIn 0.22s ease;
        }
        .toast.error { border-left: 3px solid var(--danger); }
        .toast.error i { color: var(--danger); }
        .toast.success { border-left: 3px solid var(--success); }
        .toast.success i { color: var(--success); }
        .toast.leaving { animation: toastOut 0.22s ease forwards; }

        @keyframes toastIn { from { transform: translateX(24px); opacity: 0; } to { transform: none; opacity: 1; } }
        @keyframes toastOut { to { transform: translateX(24px); opacity: 0; } }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: 0.01ms !important; transition-duration: 0.01ms !important; }
        }
    </style>
    @stack('styles')
</head>
<body>
    <div id="toast-container"></div>

    @yield('content')

    <script>
        /** A transient message. Text is set as text, never as markup. */
        function showToast(type, message) {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');

            toast.className = `toast ${type}`;
            toast.setAttribute('role', type === 'error' ? 'alert' : 'status');
            toast.innerHTML = `<i class="fas fa-${type === 'success' ? 'circle-check' : 'circle-exclamation'}"></i><span></span>`;
            toast.querySelector('span').textContent = message;
            container.appendChild(toast);

            setTimeout(() => {
                toast.classList.add('leaving');
                setTimeout(() => toast.remove(), 240);
            }, 4000);
        }
    </script>

    @stack('scripts')
</body>
</html>
