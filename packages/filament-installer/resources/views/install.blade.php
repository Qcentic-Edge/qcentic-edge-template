<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Install — {{ config('app.name') }}</title>
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

        ul { list-style: none; margin: 0 0 1.75rem; padding: 0; }

        li {
            display: flex;
            align-items: baseline;
            gap: 0.6rem;
            padding: 0.55rem 0;
            border-bottom: 1px solid var(--line);
        }

        li:last-child { border-bottom: 0; }

        .mark { font-weight: 600; width: 1.1rem; flex: none; }
        .ok .mark { color: var(--accent); }
        .fail .mark { color: #e5484d; }
        .fail .detail { color: #e5484d; }
        .detail { display: block; color: var(--dim); font-size: 12px; word-break: break-all; }

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

        button:hover:not(:disabled) { background: rgba(255, 255, 255, 0.85); }
        button:disabled { opacity: 0.35; cursor: not-allowed; }

        .error {
            margin: 0 0 1.25rem;
            padding: 0.7rem 1rem;
            border: 1px solid #e5484d;
            border-radius: 0.5rem;
            color: #e5484d;
        }

        fieldset {
            margin: 0 0 1.75rem;
            padding: 0 0 1.25rem;
            border: 0;
            border-bottom: 1px solid var(--line);
        }

        legend {
            padding: 0;
            font-family: 'Space Grotesk', 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-weight: 600;
        }

        label { display: block; margin-top: 0.9rem; color: var(--dim); font-size: 13px; }

        input {
            margin-top: 0.3rem;
            width: 100%;
            padding: 0.55rem 0.75rem;
            border: 1px solid var(--line);
            border-radius: 0.5rem;
            background: var(--bg);
            color: var(--ink);
            font: inherit;
        }

        input:focus { outline: 2px solid var(--accent); outline-offset: 1px; }

        .field-error { color: #e5484d; font-size: 12px; }

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
        <h1>{{ config('app.name') }}</h1>
        <p class="sub">First-run setup. Everything must be green before migrations can run.</p>

        @if (session('installer_error'))
            <p class="error" role="alert">{{ session('installer_error') }}</p>
        @endif

        <ul>
            @foreach ($checks as $check)
                <li class="{{ $check['ok'] ? 'ok' : 'fail' }}">
                    <span class="mark">{{ $check['ok'] ? '✓' : '✗' }}</span>
                    <span>
                        {{ $check['label'] }}
                        @if ($check['detail'])
                            <span class="detail">{{ $check['detail'] }}</span>
                        @endif
                    </span>
                </li>
            @endforeach
        </ul>

        <form method="post" action="{{ route('installer.run') }}">
            @csrf

            @if ($createUser)
                <fieldset>
                    <legend>Super admin</legend>

                    <label>
                        Name
                        <input type="text" name="name" value="{{ old('name') }}" required autocomplete="name">
                        @error('name') <span class="field-error">{{ $message }}</span> @enderror
                    </label>

                    <label>
                        Email
                        <input type="email" name="email" value="{{ old('email') }}" required autocomplete="email">
                        @error('email') <span class="field-error">{{ $message }}</span> @enderror
                    </label>

                    <label>
                        Password (min 12 chars, upper + lower case, number, symbol)
                        <input type="password" name="password" required autocomplete="new-password" minlength="12">
                        @error('password') <span class="field-error">{{ $message }}</span> @enderror
                    </label>
                </fieldset>
            @endif

            <button type="submit" @disabled(! $ready)>
                Run migrations &amp; finish setup
            </button>
        </form>
    </main>

    <a class="qcentic" href="https://qcentic.com" rel="noopener" target="_blank">
        <span>Built by</span>
        <img src="{{ asset('logo/qcentic-lockup-pulse-on-dark.svg') }}" alt="Qcentic">
    </a>
</body>
</html>
