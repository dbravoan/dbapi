# AGENTS.md — Navigation map for AI agents working on `dbapi`

> This file is the **entry point** for any agent that opens this repo. It is
> NOT a rules bible: it is a **map**. Use progressive disclosure — read only
> what you need, when you need it.

---

## 1. Before doing anything (mandatory)

1. Run `./init.sh` and verify it exits 0. If it fails, **stop** and fix the
   environment / harness invariant before touching code.
2. Read `progress/current.md` to understand where the previous session left off.
3. Read `feature_list.json` and pick **one** task with `status: "pending"`.
   Never work on more than one at a time.

## 2. Repository map

| Path | What it contains | When to read it |
|------|------------------|-----------------|
| `feature_list.json` | Single source of truth for what to work on (pending / in_progress / done / blocked) | Every session, at start |
| `progress/current.md` | Live state of the active session | Every session, at start |
| `progress/history.md` | Append-only journal of past sessions | When you need historical context |
| `progress/impl_<feature>.md` | Implementer's report for a feature (files touched, test output, decisions) | When reviewing a finished implementation |
| `progress/review_<feature>.md` | Reviewer's checklist verdict for a feature | Before declaring a feature `done` |
| `docs/architecture.md` | What "doing a good job" means in this codebase (DDD layering, CQRS, multitenancy) | Before implementing anything |
| `docs/conventions.md` | Style, naming, namespaces, file layout, response shape | Before writing code |
| `docs/verification.md` | How to prove a feature really works | Before flipping a feature to `done` |
| `CHECKPOINTS.md` | Objective end-state checks for the whole repo | For self-audit and reviewer pass |
| `OPENCODE.md` | Role guard for opencode (forces top-level agent into `leader`) | Loaded automatically by opencode |
| `.agents/agents/leader.md` | Definition of the `leader` subagent (orchestrator) | If you are orchestrating |
| `.agents/agents/implementer.md` | Definition of the `implementer` subagent (writes code + tests) | If you are implementing |
| `.agents/agents/reviewer.md` | Definition of the `reviewer` subagent (validates against docs + CHECKPOINTS) | If you are reviewing |
| `.agents/skills/` | Reusable, domain-specific skills (DDD skeleton, forms, translatable, Laravel patterns, …) | When the task matches a skill |
| `src/` | Application code (DDD modules: Blogging, Identity, Forms, Pages, Languages, TodoList, Shared) | When implementing |
| `app/` | Laravel glue (Models, Providers, Middleware, Console commands) | When wiring infrastructure |
| `tests/` and `src/**/Tests/` | PHPUnit feature + unit tests | When verifying |
| `routes/api.php` | All HTTP routes, grouped by module gate | When exposing or auditing endpoints |
| `vendor/dbravoan/dba-ddd-skeleton/` | The DDD skeleton package (real namespace: `Dba\DddSkeleton\…`) | When you need a base class |
| `.agents/scripts/commit.sh` | Safe commit harness (pre-commit checks, master guard) | When committing |
| `.agents/scripts/push.sh` | Safe push harness (protected branch gate) | When pushing |
| `.agents/scripts/human-gate.sh` | CI/CD merge gate (blocks non-human master merges) | In CI pipeline |

## 3. Hard rules (non-negotiable)

- **One feature at a time.** No mixing scopes inside one session.
- **No `done` without green checks.** `./init.sh` must exit 0 AND the relevant
  tests must pass before you flip the status.
- **State on disk, not in chat.** Document what you are doing in
  `progress/current.md` as you go, not at the end.
- **Subagents write reports, not transcripts.** When the `leader` dispatches an
  `implementer` or `reviewer`, the subagent writes a markdown report to
  `progress/` and returns only the file reference. Avoid the
  "telephone-game" of pasting long outputs back into chat.
- **Respect the DDD/CQRS layout.** `Domain → Application → Infrastructure`,
  always (see `docs/architecture.md`). Controllers dispatch via the bus; they
  never call repositories directly.
- **Use the real package namespace** `Dba\DddSkeleton\…` — never invent
  `Dbravoan\DbaSkeletonDdd\…` (an outdated skill mentions it; ignore that).
- **Leave the repo cleaner than you found it.** No `dd()`, no `Log::debug`
  scaffolding, no orphan TODOs.

## 4. How to pick a task

```
1. Open feature_list.json
2. Filter features where status == "pending"
3. Pick the one with the smallest "id" (or one explicitly requested)
4. Flip its status to "in_progress" and save
5. Append a block to progress/current.md:
   - feature id + title
   - start timestamp (UTC)
   - short plan (3–6 bullets)
```

## 5. Session lifecycle (closing)

Before ending a session:

1. Run `./init.sh` — must be all green.
2. If the work is complete: flip `status: "done"` in `feature_list.json`.
3. Move the session block from `progress/current.md` to the bottom of
   `progress/history.md` (append-only).
4. Empty `progress/current.md` back to the template (heading + empty section).
5. Confirm there are no stray files, no debug scaffolding, no orphan branches.

## 6. If you are blocked

- Re-read the relevant `docs/` section. Then the relevant skill in `.agents/skills/`.
- If a tool does not behave as expected, **do not invent a workaround**.
  Document the blocker in `progress/current.md`, flip the feature to
  `blocked` in `feature_list.json`, and stop the session.

## 7. Two-line summary for impatient agents

> Read `progress/current.md`, run `./init.sh`, pick one `pending` feature from
> `feature_list.json`, dispatch the appropriate subagent (see
> `.agents/agents/`), let it write its report to `progress/`, then run the
> `reviewer` subagent before flipping to `done`. Tests must be green.
