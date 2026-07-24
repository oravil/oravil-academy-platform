# CLAUDE.md — Oravil Academy Platform

> Standing instructions for Claude Code. Read fully before any action.
> Detailed current state, open bugs, and task order: see `docs/handoff/OA-HANDOFF-001.md`.
> Governing documents live in the SEPARATE docs repository `oravil/oravil-academy`.

---

## 1. What this project is

Oravil Academy Platform — the implementation repository for Oravil Academy v0.1,
an MVP learning platform validating a learning methodology with a small cohort
of manually provisioned pilot learners. One authenticated role exists: **Learner**.

Two repositories. They NEVER mix (ADR-0002):

| Repository | Contains | NEVER put here |
|---|---|---|
| `oravil-academy` (docs) | Governance, curriculum, ADRs, MVP specs, sprint logs, reviews | Application code, migrations, package files |
| `oravil-academy-platform` (this repo) | Laravel backend, React frontend, CI, Docker | ADRs, MVP specs, curriculum, governance docs |

The documentation repository is the **Source of Truth**. Documentation precedes
code — always. If a task changes a schema, contract, architecture, or business
rule: the governing document is amended and committed FIRST (docs repo), only
then is the implementing code written (this repo). Never duplicate a governing
document into this repository.

Governing document hierarchy (higher wins on conflict):
Constitution → Lifecycle → Standards → OA-GUIDE-001 → ADRs → MVP docs (OA-MVP-001..010).
When a prompt conflicts with the documents, the documents win — say so instead
of silently complying.

## 2. Stack (decided — do not change)

- Backend: Laravel 12, PHP **8.5** (composer `^8.5`, CI pinned 8.5; amended
  from 8.4 per ADR-0007 — 8.4 is not apt-installable on this server's OS)
- Auth: **Sanctum stateful SPA cookie authentication** (ADR-0006). No bearer
  tokens. No localStorage. CSRF via `/sanctum/csrf-cookie` + `X-XSRF-TOKEN`.
- Database: PostgreSQL 16 (Docker Compose service `postgres`)
- Frontend: React 18 + Vite + TypeScript, TanStack Query, React Hook Form + Zod,
  Tailwind 4, shadcn/ui
- Testing: Pest (backend), Vitest + Testing Library (frontend)
- Quality: Pint, PHPStan, ESLint, Prettier, Husky, lint-staged, Commitlint
- Learner presentation: **Arabic-only** in v0.1 (ADR-0008). No i18n library,
  translation framework, language switcher, or `learners.preferred_language`.
  API contracts stay English; learner-facing Arabic copy is keyed on
  `error.code` — the English `error.message` is never shown to learners.

Identity model (ADR-0005): table `learners` ONLY —
`id uuid PK default gen_random_uuid()`, `email text unique`, `display_name text`,
`password_hash text`, `enrolled_at timestamptz default now()`.
No `users` table. No `password_reset_tokens`. No timestamps columns.
Learners are provisioned manually. There is NO self-registration.

## 3. Hard scope walls

Permanently out of scope unless a future ADR says otherwise:
Register, Forgot Password, Email Verification, RBAC, Admin, Instructor,
dashboards, certificates, gamification, notifications.

You implement EXACTLY the declared scope of the current task. "It would be easy
to also add X" is not authorization. Record X as a Product Backlog candidate and
continue. One slice = one PR.

## 4. Discipline (OA-DIR-001 — violations invalidate the work)

1. State which repository you are operating in before your first action.
2. Never claim completion you cannot prove. "Complete" requires the artifact at
   a real path or tests proving behaviour. If you did not run the tests, say
   "tests not executed". Honest incompleteness is acceptable; inflated status
   is a critical violation.
3. Allowed status vocabulary: `Working` / `Partially working` / `Blocked` / `Unknown`.
4. Runtime bugs: identify the VERIFIED root cause first, then implement the
   smallest possible fix. Do not stack speculative fixes.
5. Never modify the schema without a migration AND explicit approval.
   OA-MVP-006 must be amended first.
6. Never introduce a new dependency without asking first.
7. Stop and escalate (do not decide alone) when a task requires: changing
   architecture, a contract or schema, adding a dependency, changing repo
   organization, touching auth strategy, or interpreting an ambiguous requirement.
8. Conventional Commits only, referencing the governing document or review ID.
   Example: `fix(bootstrap): fail hard when migrate fails — OA-AUDIT-001 PMV-001`.
9. Every fix ships with: automated tests + browser verification + regression
   verification. Verification must include executable evidence (command + output).
10. End every session with the mandatory report:
    (1) repository operated in, (2) declared vs delivered scope, (3) files
    changed, (4) tests executed or not + results, (5) items deliberately not
    done and why, (6) backlog candidates, (7) open questions.

## 5. Environment & commands

Server: Ubuntu (EC2), project at `~/oravil-academy-platform`, user `ubuntu`.
Known environmental history (check `.env` FIRST when anything database-related
fails): pdo_pgsql was missing once; local PostgreSQL conflicted on port 5432;
`backend/.env` credentials diverged from Docker Compose. Docker credentials:
db `oravil_academy`, user `oravil` (password in `.env.docker`).

```bash
make bootstrap        # full setup
make dev              # docker up + artisan serve (8000) + vite dev (5173)
make test             # backend + frontend tests
make lint             # pint + phpstan + eslint
cd backend && php artisan migrate:status   # DB ground truth
cd backend && php artisan test             # Pest (needs postgres up)
cd frontend && pnpm test                   # Vitest
```

API base: `http://localhost:8000`, prefix `/v1`. Frontend dev: `http://localhost:5173`.
Existing endpoints: `POST /v1/assignments/{assignment_id}/submissions`,
`POST /v1/auth/login`, `POST /v1/auth/logout`, `GET /v1/auth/me`,
`GET /v1/learners/me/progress/{module_id}`, `GET /v1/lessons/{lesson_id}`,
`GET /v1/modules/{module_id}/completion`, `GET /v1/modules/{module_id}/overview`,
`GET /v1/modules/{module_id}/survey`, `POST /v1/surveys/{survey_id}/responses`.
All require `auth:sanctum` EXCEPT `POST /v1/auth/login` (public by design —
a learner is not authenticated yet when logging in).
Error envelope (all endpoints): `{"error": {"code", "message", "fields?"}}`;
401 `unauthenticated` / `invalid_credentials`, 403 `forbidden`,
419 `CSRF_TOKEN_MISMATCH`, 422 `validation_error`.

## 6. Current state pointer (as of 2026-07-24)

Detailed current state, open items, and task order: `docs/handoff/OA-HANDOFF-001.md`.

- OA-MVP-010 Steps 1-9 are complete. All six vertical slices (VS-001
  Authentication through VS-006 Post-Module Survey) are implemented, tested,
  and browser-verified; Step 9 (Post-Merge / End-to-End Verification) is
  closed at commit `74432dc`.
- Next phase: pre-pilot. Pre-launch items live in the handoff and
  `docs/PRODUCT_BACKLOG.md` (docs repo) — e.g. OA-BL-026 learner provisioning
  tooling and the Arabic learner presentation work governed by ADR-0008.
  No new vertical slice is authorized by this snapshot.
- Definition of Done for every slice: the eleven criteria in OA-MVP-010
  (docs repo) — including CI proving behaviour, not just style.
