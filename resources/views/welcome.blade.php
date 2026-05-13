<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DB API</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg:           #0f1518;
            --bg-elev:      #121c20;
            --panel:        rgba(18, 28, 32, 0.74);
            --line:         rgba(120, 255, 187, 0.28);
            --text:         #effff8;
            --muted:        #a7c2b8;
            --accent:       #00ff99;
            --accent-strong:#00db83;
            --shadow:       0 30px 80px rgba(0, 0, 0, 0.48);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Space Grotesk", sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at 15% 10%, rgba(0, 255, 153, 0.14), transparent 38%),
                radial-gradient(circle at 85% 25%, rgba(0, 255, 153, 0.10), transparent 36%),
                linear-gradient(145deg, var(--bg) 0%, var(--bg-elev) 100%);
        }

        .ambient {
            position: fixed;
            inset: 0;
            pointer-events: none;
            opacity: 0.26;
            background-image:
                linear-gradient(rgba(0, 255, 153, 0.06) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0, 255, 153, 0.06) 1px, transparent 1px);
            background-size: 42px 42px;
            mask-image: radial-gradient(circle at 50% 40%, #000 25%, transparent 80%);
        }

        .shell {
            width: min(1080px, 92vw);
            margin: 34px auto;
            border: 1px solid var(--line);
            border-radius: 18px;
            background: var(--panel);
            backdrop-filter: blur(6px);
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
            animation: rise 0.9s ease-out;
        }

        .shell::before {
            content: "";
            position: absolute;
            inset: -2px;
            background: linear-gradient(120deg, transparent 20%, rgba(0, 255, 153, 0.16), transparent 80%);
            pointer-events: none;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 20px;
            border-bottom: 1px solid var(--line);
        }

        .brand {
            font-family: "JetBrains Mono", monospace;
            font-weight: 700;
            letter-spacing: 0.03em;
            font-size: 0.95rem;
        }

        .brand .accent { color: var(--accent); }

        .top-links {
            display: flex;
            gap: 16px;
            font-size: 0.9rem;
        }

        .top-links a {
            color: var(--muted);
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .top-links a:hover { color: var(--text); }

        .hero {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 22px;
            padding: 44px 28px 34px;
        }

        .kicker {
            margin: 0;
            font-family: "JetBrains Mono", monospace;
            color: var(--accent);
            font-size: 0.86rem;
            letter-spacing: 0.06em;
            opacity: 0;
            animation: fade-up 0.6s 0.15s forwards;
        }

        h1 {
            margin: 12px 0 16px;
            font-size: clamp(2rem, 4vw, 3.6rem);
            line-height: 1.06;
            letter-spacing: -0.03em;
            opacity: 0;
            animation: fade-up 0.6s 0.25s forwards;
        }

        .lead {
            margin: 0;
            color: var(--muted);
            max-width: 56ch;
            font-size: 1.05rem;
            line-height: 1.58;
            opacity: 0;
            animation: fade-up 0.6s 0.38s forwards;
        }

        .actions {
            display: flex;
            gap: 12px;
            margin-top: 26px;
            flex-wrap: wrap;
            opacity: 0;
            animation: fade-up 0.6s 0.5s forwards;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border-radius: 10px;
            padding: 12px 18px;
            text-decoration: none;
            font-family: "Space Grotesk", sans-serif;
            font-weight: 600;
            letter-spacing: 0.01em;
            transition: transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
        }

        .btn-primary {
            background: linear-gradient(120deg, var(--accent) 0%, var(--accent-strong) 100%);
            color: #042513;
            box-shadow: 0 10px 28px rgba(0, 255, 153, 0.28);
        }

        .btn-secondary {
            color: var(--text);
            border: 1px solid var(--line);
            background: rgba(8, 12, 14, 0.46);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 28px rgba(0, 0, 0, 0.34);
        }

        .panel {
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 18px;
            background: rgba(8, 14, 16, 0.58);
            font-family: "JetBrains Mono", monospace;
            font-size: 0.86rem;
            opacity: 0;
            animation: fade-up 0.6s 0.62s forwards;
        }

        .panel p { margin: 0 0 12px; color: var(--muted); }

        .code {
            display: block;
            margin-top: 8px;
            color: var(--text);
            white-space: nowrap;
            overflow: auto;
            padding: 10px 12px;
            border-radius: 10px;
            background: rgba(0, 0, 0, 0.38);
            border: 1px solid rgba(120, 255, 187, 0.2);
        }

        .meta {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            padding: 0 28px 30px;
        }

        .pill {
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 14px;
            background: rgba(7, 12, 14, 0.5);
        }

        .pill .label {
            margin: 0 0 6px;
            color: var(--muted);
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .pill .value {
            margin: 0;
            font-size: 1.05rem;
        }

        @keyframes rise {
            from { transform: translateY(8px); opacity: 0; }
            to   { transform: translateY(0);   opacity: 1; }
        }

        @keyframes fade-up {
            from { transform: translateY(8px); opacity: 0; }
            to   { transform: translateY(0);   opacity: 1; }
        }

        @media (max-width: 860px) {
            .hero  { grid-template-columns: 1fr; }
            .meta  { grid-template-columns: 1fr; }
            .topbar { flex-direction: column; gap: 10px; align-items: flex-start; }
        }
    </style>
</head>
<body>
    @php
        $apiDocsPath = data_get(config('l5-swagger.documentations'), 'default.routes.api', 'api/documentation');
        $swaggerConstHost = config('l5-swagger.defaults.constants.L5_SWAGGER_CONST_HOST', 'http://localhost');
    @endphp

    <div class="ambient" aria-hidden="true"></div>

    <main class="shell">
        <header class="topbar">
            <div class="brand">&gt;<span class="accent">_</span>dbapi</div>
            <nav class="top-links" aria-label="Main navigation">
                <a href="{{ url('/') }}">Home</a>
                <a href="{{ url($apiDocsPath) }}">Swagger UI</a>
                <a href="{{ url('/docs') }}">Documentation</a>
                <a href="https://github.com/dbravoan" target="_blank" rel="noopener">GitHub</a>
                <a href="https://github.com/dbravoan/dbapi" target="_blank" rel="noopener">Repo</a>
            </nav>
        </header>

        <section class="hero">
            <div>
                <p class="kicker">LARAVEL 13 &middot; DDD &middot; MULTI-TENANCY</p>
                <h1>DB API — built for developers.</h1>
                <p class="lead">
                    Consulta endpoints, autenticacion y contratos OpenAPI en segundos.
                    Esta portada conecta directamente con la documentacion interactiva de tu API.
                </p>
                <div class="actions">
                    <a class="btn btn-primary" href="{{ url($apiDocsPath) }}">Swagger UI</a>
                    <a class="btn btn-secondary" href="{{ url('/docs') }}">Full Documentation</a>
                    <a class="btn btn-secondary" href="https://github.com/dbravoan/dbapi" target="_blank" rel="noopener">Source Code</a>
                </div>
            </div>

            <aside class="panel" aria-label="Quick start">
                <p>Quick start con Sail</p>
                <span class="code">./vendor/bin/sail up -d</span>
                <span class="code">sail artisan l5-swagger:generate</span>
                <span class="code">GET {{ url($apiDocsPath) }}</span>
            </aside>
        </section>

        <section class="meta">
            <article class="pill">
                <p class="label">Stack</p>
                <p class="value">Laravel {{ Illuminate\Foundation\Application::VERSION }}</p>
            </article>
            <article class="pill">
                <p class="label">Auth</p>
                <p class="value">Passport + Bearer Tokens</p>
            </article>
            <article class="pill">
                <p class="label">Routing</p>
                <p class="value">/{tenant}/{version}/...</p>
            </article>
        </section>
    </main>
</body>
</html>
