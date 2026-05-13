<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Docs — DB API</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0f1518;
            --surface: #162125;
            --line: rgba(120, 255, 187, 0.18);
            --text: #eafff3;
            --muted: #90aca0;
            --accent: #00ff99;
            --accent-dim: rgba(0, 255, 153, 0.10);
        }
        * { box-sizing: border-box; margin: 0; }
        body {
            font-family: Inter, sans-serif;
            background: var(--bg);
            color: var(--text);
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: 260px;
            flex-shrink: 0;
            border-right: 1px solid var(--line);
            padding: 28px 20px;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
        }
        .sidebar-brand {
            font-family: "JetBrains Mono", monospace;
            font-weight: 700;
            font-size: 0.9rem;
            margin-bottom: 24px;
            color: var(--accent);
        }
        .sidebar nav { display: flex; flex-direction: column; gap: 4px; }
        .sidebar a {
            color: var(--muted);
            text-decoration: none;
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 0.88rem;
            transition: all 0.15s;
        }
        .sidebar a:hover {
            color: var(--text);
            background: var(--accent-dim);
        }
        .sidebar .section-label {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--accent);
            margin-top: 16px;
            margin-bottom: 4px;
            padding: 0 10px;
        }
        .main {
            flex: 1;
            max-width: 900px;
            padding: 48px 40px;
        }
        .main h1 {
            font-size: 2.2rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            margin-bottom: 8px;
        }
        .main h2 {
            font-size: 1.4rem;
            font-weight: 600;
            margin-top: 40px;
            margin-bottom: 12px;
            padding-bottom: 6px;
            border-bottom: 1px solid var(--line);
        }
        .main h3 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-top: 24px;
            margin-bottom: 8px;
        }
        .main p, .main li {
            color: var(--muted);
            line-height: 1.65;
            font-size: 0.95rem;
        }
        .main p { margin: 10px 0; }
        .main ul { padding-left: 20px; }
        .main li { margin: 4px 0; }
        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 12px;
            margin: 16px 0;
        }
        .card {
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 16px;
            background: var(--surface);
            transition: border-color 0.15s;
        }
        .card:hover { border-color: var(--accent); }
        .card .title {
            font-weight: 600;
            margin-bottom: 4px;
        }
        .card .desc {
            font-size: 0.85rem;
            color: var(--muted);
        }
        .card a {
            color: inherit;
            text-decoration: none;
        }
        .card a::after { content: " →"; color: var(--accent); }
        code {
            font-family: "JetBrains Mono", monospace;
            font-size: 0.85rem;
            background: var(--accent-dim);
            padding: 2px 6px;
            border-radius: 4px;
        }
        .main a { color: var(--accent); text-decoration: none; }
        .main a:hover { text-decoration: underline; }
        .pill {
            display: inline-block;
            font-size: 0.75rem;
            padding: 2px 8px;
            border-radius: 20px;
            background: var(--accent-dim);
            color: var(--accent);
            margin-right: 4px;
        }
        ::-webkit-scrollbar { width: 4px; }
        ::-webkit-scrollbar-track { background: var(--bg); }
        ::-webkit-scrollbar-thumb { background: var(--line); border-radius: 2px; }
        @media (max-width: 720px) {
            .sidebar { display: none; }
            .main { padding: 24px 20px; }
        }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="sidebar-brand">dbapi / docs</div>
    <nav>
        <div class="section-label">API Reference</div>
        <a href="/api/documentation">Swagger UI</a>
        <a href="/api/swagger-docs">OpenAPI JSON</a>

        <div class="section-label">Guides</div>
        <a href="#user-manual">User Manual</a>
        <a href="#development">Development Guide</a>
        <a href="#architecture">Architecture</a>
        <a href="#conventions">Conventions</a>
        <a href="#testing">Testing</a>
        <a href="#deployment">Deployment</a>

        <div class="section-label">Workflow</div>
        <a href="#github-flow">GitHub Flow</a>
        <a href="#agent-harness">Agent Harness</a>

        <div class="section-label">Links</div>
        <a href="/">Home</a>
        <a href="{{ url('/docs/user-manual.md') }}" onclick="event.preventDefault(); document.getElementById('um').scrollIntoView({behavior:'smooth'})">User Manual (local)</a>
    </nav>
</aside>

<main class="main">

<h1>Documentation</h1>
<p>All resources for understanding, using, and extending the DB API.</p>

<div class="card-grid">
    <div class="card"><a href="/api/documentation">
        <div class="title">Swagger UI</div>
        <div class="desc">Interactive API docs — try endpoints live</div>
    </a></div>
    <div class="card"><a href="/api/swagger-docs">
        <div class="title">OpenAPI JSON</div>
        <div class="desc">Raw OpenAPI 3.0 specification</div>
    </a></div>
    <div class="card"><a href="#user-manual">
        <div class="title">User Manual</div>
        <div class="desc">API consumer quick-start guide</div>
    </a></div>
    <div class="card"><a href="#development">
        <div class="title">Development Guide</div>
        <div class="desc">How to develop with DDD/CQRS</div>
    </a></div>
    <div class="card"><a href="#architecture">
        <div class="title">Architecture</div>
        <div class="desc">DDD layering, CQRS, multitenancy</div>
    </a></div>
    <div class="card"><a href="#conventions">
        <div class="title">Conventions</div>
        <div class="desc">Naming, namespaces, file layout</div>
    </a></div>
</div>

{{-- ─────────────────────────────────────────────────────────────────────── --}}
<h2 id="user-manual">User Manual <a href="#user-manual" style="font-size:0.7em">¶</a></h2>

<p><strong>Base URL:</strong> <code>/{tenant}/{version}/...</code> (e.g. <code>/acme/v1/posts</code>)</p>
<p><strong>Auth:</strong> Writes require <code>Authorization: Bearer &lt;token&gt;</code> (Passport).</p>

<h3>Standard response</h3>
<pre style="background:var(--surface);padding:12px;border-radius:8px;border:1px solid var(--line);font-family:'JetBrains Mono',monospace;font-size:0.85rem;overflow-x:auto;">
{ "success": true, "data": { ... }, "message": "..." }
{ "success": false, "message": "...", "errors": { "field": ["..."] } }</pre>

<h3>Status codes</h3>
<ul>
    <li><code>200</code> OK &middot; <code>201</code> Created &middot; <code>202</code> Accepted (async)</li>
    <li><code>400</code> Bad request &middot; <code>401</code> Unauthenticated &middot; <code>403</code> Module not enabled</li>
    <li><code>404</code> Not found &middot; <code>422</code> Validation error &middot; <code>429</code> Rate limited</li>
</ul>

<h3>Modules &amp; endpoints (summary)</h3>
<table style="width:100%;border-collapse:collapse;font-size:0.88rem;margin:12px 0;">
    <tr style="border-bottom:1px solid var(--line);"><th style="text-align:left;padding:6px 8px;">Module</th><th style="text-align:left;padding:6px 8px;">Gate</th><th style="text-align:left;padding:6px 8px;">Endpoints</th></tr>
    <tr><td style="padding:6px 8px;">Blog</td><td style="padding:6px 8px;"><code>blog</code></td><td style="padding:6px 8px;">posts, categories, tags — CRUD + search</td></tr>
    <tr><td style="padding:6px 8px;">Forms</td><td style="padding:6px 8px;"><code>forms</code></td><td style="padding:6px 8px;">create, find, submit (public)</td></tr>
    <tr><td style="padding:6px 8px;">Identity</td><td style="padding:6px 8px;">—</td><td style="padding:6px 8px;">users — always available, no gate</td></tr>
    <tr><td style="padding:6px 8px;">Languages</td><td style="padding:6px 8px;"><code>languages</code></td><td style="padding:6px 8px;">list, find, create</td></tr>
    <tr><td style="padding:6px 8px;">Pages</td><td style="padding:6px 8px;"><code>pages</code></td><td style="padding:6px 8px;">find, create, update (block editor)</td></tr>
    <tr><td style="padding:6px 8px;">TodoList</td><td style="padding:6px 8px;"><code>todolist</code></td><td style="padding:6px 8px;">find, create, update tasks</td></tr>
</table>

<p>See <code>docs/user-manual.md</code> for full endpoint tables and curl examples.</p>

{{-- ─────────────────────────────────────────────────────────────────────── --}}
<h2 id="development">Development Guide <a href="#development" style="font-size:0.7em">¶</a></h2>

<p>How to extend the API with new aggregates, commands, queries, and handlers.</p>

<h3>File layout per aggregate</h3>
<pre style="background:var(--surface);padding:12px;border-radius:8px;border:1px solid var(--line);font-family:'JetBrains Mono',monospace;font-size:0.85rem;overflow-x:auto;">
src/{Context}/{Aggregate}/
├── Domain/                    # Pure PHP — no Laravel, no Eloquent
│   ├── {Aggregate}.php        (extends AggregateRoot)
│   ├── {Aggregate}Id.php      (extends Uuid)
│   ├── {Prop}ValueObject.php
│   ├── *CreatedDomainEvent.php
│   └── {Aggregate}Repository.php  (interface)
├── Application/               # CQRS handlers + response DTOs
│   ├── Create/   Command + Handler
│   ├── Find/     Query   + Handler
│   ├── Update/   Command + Handler
│   ├── SearchByCriteria/
│   └── Response/ {Aggregate}Response.php
├── Infrastructure/            # Laravel-specific wiring
│   ├── Controller/            (invokable, extends ApiController)
│   ├── Persistence/           (Elouqent*Repository)
│   └── Module/                (ModuleProvisioner, if new tables)
└── Tests/Domain/              (unit tests)
</pre>

<h3>Adding a new feature (step by step)</h3>
<ol>
    <li>Create Domain layer: aggregate root + VOs + domain event + repository interface</li>
    <li>Create Application layer: Command/Query + Handler + Response DTO</li>
    <li>Create Infrastructure layer: Controller (invokable, <code>#[OA\*]</code> annotations) + Persistence repository</li>
    <li>Wire it up: bind repository in <code>RepositoryServiceProvider</code>, register handler in <code>DomainServiceProvider</code>, add route in <code>routes/api.php</code></li>
    <li>Write Feature test in <code>tests/Feature/</code> (mock buses, cover 200/401/403/404/422)</li>
    <li>Verify: <code>php artisan test && php artisan l5-swagger:generate && ./init.sh</code></li>
</ol>

<p>See <code>docs/development.md</code> for full details.</p>

{{-- ─────────────────────────────────────────────────────────────────────── --}}
<h2 id="architecture">Architecture <a href="#architecture" style="font-size:0.7em">¶</a></h2>

<p>Three patterns layered together:</p>
<ul>
    <li><strong>DDD</strong> — 6 bounded contexts (<code>Blogging</code>, <code>Identity</code>, <code>Forms</code>, <code>Language</code>, <code>PageManagement</code>, <code>TodoList</code>). Each aggregate has <code>Domain → Application → Infrastructure</code>. Inner layers never depend on outer layers.</li>
    <li><strong>CQRS</strong> — Writes go through a <code>CommandBus</code>, reads through a <code>QueryBus</code>. Handlers are single-method classes. Domain events recorded on aggregates and dispatched by the bus.</li>
    <li><strong>Table-per-tenant</strong> — URL <code>/{tenant}/{version}/...</code> resolves to a tenant whose <code>app_id</code> prefixes all tables (<code>acme_posts</code>, <code>acme_users</code>).</li>
</ul>

<h3>Middleware pipeline</h3>
<pre style="background:var(--surface);padding:12px;border-radius:8px;border:1px solid var(--line);font-family:'JetBrains Mono',monospace;font-size:0.85rem;overflow-x:auto;">
Request → identify_tenant → api.version → tenant → require.module:X → auth:api → Controller
         (resolve)         (validate)     (set)   (gate)            (token)    (dispatch bus)
</pre>

<h3>Identity is cross-cutting</h3>
<p>User routes are <strong>not</strong> behind a <code>require.module</code> gate. Users are foundational for authentication (Passport), and every tenant needs user resolution regardless of which business modules are enabled.</p>

<p>See <code>docs/architecture.md</code> for full details.</p>

{{-- ─────────────────────────────────────────────────────────────────────── --}}
<h2 id="conventions">Conventions <a href="#conventions" style="font-size:0.7em">¶</a></h2>

<ul>
    <li><code>declare(strict_types=1)</code> on every file under <code>src/</code> and <code>app/</code></li>
    <li>Classes are <code>final</code>; VOs and DTOs are <code>final readonly</code></li>
    <li>Controllers: invokable, extend <code>ApiController</code>, inject <code>CommandBus</code>/<code>QueryBus</code></li>
    <li>Response envelope: <code>{"success": bool, "data": ..., "message": "..."}</code> via <code>sendResponse()</code>/<code>sendError()</code></li>
    <li>Namespaces: <code>Dbapi\</code> in <code>src/</code>, <code>App\</code> in <code>app/</code>, <code>Dba\DddSkeleton\</code> for skeleton</li>
    <li>No <code>dd()</code>, <code>dump()</code>, <code>Log::debug</code>, or TODOs without feature ref</li>
</ul>

<p>See <code>docs/conventions.md</code> for the full rule set.</p>

{{-- ─────────────────────────────────────────────────────────────────────── --}}
<h2 id="testing">Testing <a href="#testing" style="font-size:0.7em">¶</a></h2>

<table style="width:100%;border-collapse:collapse;font-size:0.88rem;margin:12px 0;">
    <tr style="border-bottom:1px solid var(--line);"><th style="text-align:left;padding:6px 8px;">Type</th><th style="text-align:left;padding:6px 8px;">Location</th><th style="text-align:left;padding:6px 8px;">What</th></tr>
    <tr><td style="padding:6px 8px;">Domain unit</td><td style="padding:6px 8px;"><code>src/{Ctx}/{Agg}/Tests/Domain/</code></td><td style="padding:6px 8px;">Aggregate + VO behaviour</td></tr>
    <tr><td style="padding:6px 8px;">Feature</td><td style="padding:6px 8px;"><code>tests/Feature/</code></td><td style="padding:6px 8px;">HTTP route via mocked buses</td></tr>
    <tr><td style="padding:6px 8px;">Unit</td><td style="padding:6px 8px;"><code>tests/Unit/</code></td><td style="padding:6px 8px;">Shared logic</td></tr>
</table>

<pre style="background:var(--surface);padding:12px;border-radius:8px;border:1px solid var(--line);font-family:'JetBrains Mono',monospace;font-size:0.85rem;overflow-x:auto;">
# Run all tests
php artisan test

# Static analysis
composer stan

# Regenerate OpenAPI docs
php artisan l5-swagger:generate
</pre>

<p>See <code>docs/verification.md</code> for the verification ladder.</p>

{{-- ─────────────────────────────────────────────────────────────────────── --}}
<h2 id="deployment">Deployment <a href="#deployment" style="font-size:0.7em">¶</a></h2>

<pre style="background:var(--surface);padding:12px;border-radius:8px;border:1px solid var(--line);font-family:'JetBrains Mono',monospace;font-size:0.85rem;overflow-x:auto;">
composer install --no-dev --optimize-autoloader
php artisan config:cache
php artisan route:cache
php artisan migrate
php artisan passport:keys
php artisan dba:tenant:provision app_demo
</pre>

<p>See <code>docs/development.md §5</code> for full deployment guide.</p>

{{-- ─────────────────────────────────────────────────────────────────────── --}}
<h2 id="github-flow">GitHub Flow <a href="#github-flow" style="font-size:0.7em">¶</a></h2>

<pre style="background:var(--surface);padding:12px;border-radius:8px;border:1px solid var(--line);font-family:'JetBrains Mono',monospace;font-size:0.85rem;overflow-x:auto;">
# Agent creates branch
git checkout -b feature/123-my-feature

# Agent commits (never to master!)
.agents/scripts/commit.sh "Add translatable Comment aggregate"

# Agent pushes
.agents/scripts/push.sh

# Human creates PR, reviews, merges to master
gh pr create --fill
</pre>

<p><span class="pill">Blocked</span> Direct commits to <code>master</code>/<code>main</code> are forbidden. Only humans may merge via PR.</p>

{{-- ─────────────────────────────────────────────────────────────────────── --}}
<h2 id="agent-harness">Agent Harness <a href="#agent-harness" style="font-size:0.7em">¶</a></h2>

<table style="width:100%;border-collapse:collapse;font-size:0.88rem;margin:12px 0;">
    <tr style="border-bottom:1px solid var(--line);"><th style="text-align:left;padding:6px 8px;">Script</th><th style="text-align:left;padding:6px 8px;">Purpose</th></tr>
    <tr><td style="padding:6px 8px;"><code>.agents/scripts/commit.sh</code></td><td style="padding:6px 8px;">Stage + test + commit. Refuses <code>master</code>. Never pushes.</td></tr>
    <tr><td style="padding:6px 8px;"><code>.agents/scripts/push.sh</code></td><td style="padding:6px 8px;">Push to origin. Refuses <code>master</code> without <code>--force</code>.</td></tr>
    <tr><td style="padding:6px 8px;"><code>.agents/scripts/human-gate.sh</code></td><td style="padding:6px 8px;">CI/CD gate: blocks agent merges to <code>master</code>.</td></tr>
</table>

</main>
</body>
</html>
