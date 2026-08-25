<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Finish install — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500&family=Space+Grotesk:wght@500;600;700&display=swap"
        rel="stylesheet"
    >
    <style>
        :root {
            --bg: #0a0a0a;
            --panel: #000000;
            --ink: #f5f5f5;
            --dim: #8a8a8a;
            --line: #2a2a2a;
            --accent: #ffffff;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            background: var(--bg);
            color: var(--ink);
            font-family: 'Inter', 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 14px;
            line-height: 1.6;
        }

        .card {
            width: 100%;
            max-width: 34rem;
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 0.75rem;
            padding: 2.5rem;
        }

        h1 {
            margin: 0 0 0.25rem;
            font-family: 'Space Grotesk', 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 1.5rem;
            font-weight: 600;
            letter-spacing: -0.02em;
        }

        .sub { margin: 0 0 1.75rem; color: var(--dim); }

        ol {
            margin: 0 0 1.75rem;
            padding-left: 1.25rem;
            color: var(--ink);
        }

        ol li { margin: 0.6rem 0; }

        code {
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size: 12px;
            padding: 0.1rem 0.35rem;
            border: 1px solid var(--line);
            border-radius: 0.25rem;
            background: var(--bg);
        }

        button {
            width: 100%;
            padding: 0.7rem 1rem;
            border: 0;
            border-radius: 0.5rem;
            background: var(--accent);
            color: var(--bg);
            font: inherit;
            font-weight: 600;
            cursor: pointer;
        }

        button:hover { background: rgba(255, 255, 255, 0.85); }

        .error {
            margin: 0 0 1.25rem;
            padding: 0.7rem 1rem;
            border: 1px solid #e5484d;
            border-radius: 0.5rem;
            color: #e5484d;
        }

        .ok-banner {
            margin: 0 0 1.25rem;
            padding: 0.7rem 1rem;
            border: 1px solid var(--line);
            border-radius: 0.5rem;
            color: var(--ink);
        }

        .qcentic {
            position: fixed;
            right: 16px;
            bottom: 16px;
            z-index: 60;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 10px 6px 12px;
            background: var(--bg);
            border: 1px solid var(--line);
            border-radius: 2px;
            text-decoration: none;
            color: var(--dim);
            font-size: 0.625rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            box-shadow: 0 4px 16px rgb(0 0 0 / 28%);
        }

        .qcentic img { height: 30px; width: auto; display: block; }
    </style>
</head>
<body>
    <main class="card">
        <h1>Database ready</h1>
        <p class="sub">
            Migrations, seeders, and the first user are done. On a stateless host
            (Magic Containers) nothing is stored on the container disk — retire
            the installer with an env var, then open the app.
        </p>

        @if (session('installer_error'))
            <p class="error" role="alert">{{ session('installer_error') }}</p>
        @endif

        <div class="ok-banner">
            Set this in your Magic Containers (or host) environment, then redeploy:
            <p style="margin: 0.75rem 0 0;"><code>INSTALLER_ENABLED=false</code></p>
        </div>

        <ol>
            <li>Open the app env settings.</li>
            <li>Set <code>INSTALLER_ENABLED</code> to <code>false</code>.</li>
            <li>Redeploy so the new env loads.</li>
            <li>Press <strong>Check</strong> below (or open the site root).</li>
        </ol>

        <form method="post" action="{{ route('installer.check') }}">
            @csrf
            <button type="submit">Check — open the app if INSTALLER_ENABLED is false</button>
        </form>
    </main>

    <a class="qcentic" href="https://qcentic.com" rel="noopener" target="_blank">
        <span>Built by</span>
        <img src="{{ asset('logo/qcentic-lockup-pulse-on-dark.svg') }}" alt="Qcentic">
    </a>
</body>
</html>
