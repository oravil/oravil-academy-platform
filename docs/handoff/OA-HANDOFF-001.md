# OA-HANDOFF-001 — Current State & Work Order

**Document ID:** OA-HANDOFF-001
**Status:** Active
**Date:** 2026-07-21
**Basis:** OA-AUDIT-001 (evidence-based audit of both repositories, 2026-07-21)
**Audience:** Claude Code (implementation agent) + Product Owner
**Location:** `oravil-academy-platform/docs/handoff/OA-HANDOFF-001.md`
**Supersedes:** ORAVIL_ACADEMY_HANDOFF.md Parts 1-3 (ChatGPT-generated — describes a state OLDER than current code; do not use)

> Rule of interpretation: every status label here was assigned from direct code
> inspection, not conversation claims. [DONE-VERIFIED] = artifact inspected at
> its real path. [DONE-CLAIMED] = asserted in the source conversation but not
> independently verified. If reality on the server disagrees with this document,
> reality wins — report the discrepancy.

---

## 1. Verified current state

### 1.1 Resolved — do NOT re-fix (OA-REV-003 findings matrix)

| Finding | Was | Now | Evidence (path) |
|---|---|---|---|
| F-1 | VS-001 implemented before foundation gate | Re-scoped procedurally; code retained | OA-REV-003 required action |
| F-2 | Default `users` table diverged from schema | `learners` matches ADR-0005 exactly; no `users`, no `password_reset_tokens` | `backend/database/migrations/0001_01_01_000000_create_learners_table.php`, `backend/app/Models/Learner.php` |
| F-3 | PHP 8.2 vs approved 8.4 | composer `^8.4`; CI pinned `8.4` | `backend/composer.json`, `.github/workflows/ci.yml` |
| F-4 | CI ran zero tests | Pest runs with postgres:16-alpine service; Vitest runs | `.github/workflows/ci.yml` |
| F-5 | Mixed cookie/localStorage auth | Cookie-only Sanctum SPA; no localStorage; CSRF via `X-XSRF-TOKEN` | `frontend/src/lib/api.ts`, `backend/bootstrap/app.php` |
| F-6 | TanStack Query / Zod unused | Both actively used | `frontend/src/features/auth/AuthContext.tsx`, `loginSchema.ts` |
| F-7 | Ad-hoc error bodies; 422 for bad credentials | Unified envelope; 401 invalid credentials; 419 `CSRF_TOKEN_MISMATCH` | `backend/bootstrap/app.php`, `LoginController.php` |

### 1.2 Implemented and inspected [DONE-VERIFIED]

- Backend VS-001: routes (`POST /v1/auth/login|logout`, `GET /v1/auth/me`),
  `LoginController` (guard `web`, `session()->regenerate()`), `LoginRequest`,
  `LearnerResource` (learner_id, email, display_name), `Learner` model
  (HasUuids, `$timestamps=false`, `getAuthPasswordName()='password_hash'`,
  cast `hashed`), migration matching ADR-0005 + `sessions` table.
- Frontend VS-001: `api.ts` (credentials include, CSRF pre-fetch), `AuthContext`
  (TanStack Query, key `['auth','learner']`), `LoginPage`, `ProtectedRoute`,
  Zod `loginSchema`, tests for each. `App.tsx`: `/login` + protected `/`
  (protected page is placeholder `<div>Welcome!</div>` — intentional).
- Tests present: `tests/Feature/Auth/AuthenticationTest.php`,
  `tests/Feature/Database/LearnerSchemaTest.php`, frontend `__tests__/*`.
- Tooling/CI/Docker per CLAUDE.md §2. Seeder creates one Test Learner
  (`test@example.com`).

### 1.3 Claimed but not independently verified [DONE-CLAIMED]

- "11 passed / 52 assertions" for `php artisan test` on the server.
- `git tag platform-foundation-v1` pushed.
- CI actually green on `main` at the latest commit.

Session zero must re-establish all three with command output.

### 1.4 Not built yet [PLANNED]

- Content tables (8): `learning_paths`, `phases`, `modules`, `lessons`,
  `assignments`, `submissions`, `surveys`, `survey_questions` — no migrations.
- Step 2 Content Seeding; VS-002 Module Overview; Steps 5-9 of OA-MVP-010.

### 1.5 Completion estimate

~35% toward v0.1 (Steps 1 and 3 of nine done in code, weighted for
foundation/tooling/governance; smoke test still open — not rounded up).

---

## 2. Open problems (the entire current work queue)

### PMV-002 — Browser session persistence bug [BLOCKED] — TOP PRIORITY

**Symptom (runtime, documented):** login returns 200 and an authenticated row
exists in `sessions`; browser refresh → `GET /v1/auth/me` returns 401; Laravel
creates a new anonymous session. Root cause **UNKNOWN**.

**Ranked suspects (from code inspection):**

**S1 — Access host vs Sanctum/CORS config (STRONGEST).** Testing happens on EC2
(`ip-172-31-26-14`). If the browser opens the frontend via a public IP/hostname
(e.g. `http://<ec2-public-ip>:5173`) then: `SANCTUM_STATEFUL_DOMAINS` (defaults
list only localhost/127.0.0.1) does not match the Origin → request not treated
as stateful; `CORS_ALLOWED_ORIGINS=http://localhost:5173` blocks credentialed
cross-origin requests; `SameSite=lax` + differing hosts can stop the cookie
being sent at all. This reproduces the symptom exactly: login "succeeds"
server-side but the cookie never returns on the next request.
**Known diagnostic gap:** the historical `.env` grep never checked
`SESSION_DRIVER` or `SANCTUM_STATEFUL_DOMAINS`. Close that gap first.

**S2 — Middleware duplication.** `routes/api.php` wraps the auth group in the
full `web` middleware group manually WHILE `bootstrap/app.php` enables
`statefulApi()` (which applies session/cookie/CSRF layers conditionally for
stateful origins). Non-standard combination; may behave differently between the
login request and the subsequent `/me` request. Only touch after S1 is
confirmed or excluded.

**S3 — Sanctum AuthenticateSession + custom password column (WEAKEST).**
`config/sanctum.php` enables `AuthenticateSession`; the model uses
`password_hash` via `getAuthPasswordName()`. A session-stored hash mismatch
would silently log out on the first post-refresh request. Test only if S1 and
S2 are excluded.

**Mandatory diagnostic sequence (in order; record every command + output):**

```bash
# D1 — what URL is the browser actually using? (ask the operator; record it)
# D2 — server env ground truth (redact secret VALUES in reports):
cd ~/oravil-academy-platform/backend
grep -E '^(APP_URL|FRONTEND_URL|SESSION_DRIVER|SESSION_DOMAIN|SESSION_SAME_SITE|SESSION_SECURE_COOKIE|SANCTUM_STATEFUL_DOMAINS|CORS_ALLOWED_ORIGINS)=' .env
# D3 — runtime config as Laravel resolves it:
php artisan config:show session | head -30
php artisan config:show sanctum
php artisan config:show cors
# D4 — browser DevTools -> Network:
#   login response: Set-Cookie present (laravel_session + XSRF-TOKEN)? Domain/SameSite/Secure attrs?
#   /v1/auth/me after refresh: Cookie header sent? Response headers?
# D5 — sessions table correlation:
php artisan tinker --execute="dd(DB::table('sessions')->orderByDesc('last_activity')->limit(5)->get(['id','user_id','last_activity']));"
```

**Decision rule:** S1 confirmed → fix is CONFIG ONLY (`SANCTUM_STATEFUL_DOMAINS`,
`CORS_ALLOWED_ORIGINS`, and if frontend+backend share one public host consider
`SESSION_DOMAIN`); no code changes; document values as comments in
`.env.example`. S1 excluded → S2 as a minimal isolated change with regression
tests. Never apply S1+S2+S3 together.

**Exit criteria (closes the bug AND the smoke test):**
Browser: Login → 200; Refresh → GET /v1/auth/me → 200
(same session id in DB, no new anonymous row)
Logout → success; /me → 401
cd backend && php artisan test → all green (report exact counts)
cd frontend && pnpm test → all green
Regression: login, logout, refresh, CSRF (419 path) re-verified

### PMV-001 — bootstrap.sh reports success when migrate fails [PLANNED]

Root cause found (OA-AUDIT-001 §6): `scripts/bootstrap.sh` line 142 —

```bash
(cd backend && php artisan migrate --force --ansi) && success "Migrations complete"
```

`set -e` does not apply to the left side of `&&`; on migrate failure the script
continues to "Bootstrap complete!". Fix (one line):

```bash
(cd backend && php artisan migrate --force --ansi) || error "Database migration failed"
success "Migrations complete"
```

Audit the rest of the script for the same `cmd && success` pattern while there.
Verification: temporarily break DB credentials → run → must exit non-zero with
the error; restore → run → completes. Commit:
`fix(bootstrap): fail hard when migrate fails — OA-AUDIT-001 PMV-001`.

### PMV-003 — Server CLI on PHP 8.5.4 vs approved 8.4 [OPEN — HUMAN DECISION]

Code and CI correctly pin 8.4. The server CLI runs 8.5.4. Options:
(a) install/switch to 8.4 on the server (`update-alternatives`), or
(b) amend the approved decision to 8.5 (docs first, then composer/CI).
**The agent does not decide this. Ask the Product Owner, then execute.**

---

## 3. Work order (strict sequence — no skipping)

| # | Task | Gate to proceed |
|---|---|---|
| 0 | Session zero: re-verify [DONE-CLAIMED] items (tests, tag, CI) with command output | Report produced |
| 1 | PMV-002 diagnosis D1-D5 → verified root cause | Root cause stated with evidence |
| 2 | PMV-002 minimal fix + exit criteria met | Smoke test CLOSED |
| 3 | PMV-001 bootstrap fix + verification | Merged referencing OA-AUDIT-001 |
| 4 | PMV-003 escalated → decision executed | PHP version aligned, documented |
| 5 | Integration checkpoint: `make bootstrap` clean on fresh clone; full test suites; CI green on main | Product Owner approves closing Foundation |
| 6 | Step 2 — Content Seeding: migrations for the 8 tables EXACTLY per OA-MVP-006 (uuid PKs, FKs, unique + check constraints), then seeders for Phase 0 Module 1 from the docs repo (4 lessons, 4 assignments, 1 survey, 3 survey questions per OA-MVP-004) | Seeded DB queryable; schema tests green |
| 7 | VS-002 — Module Overview per SPRINT-001 Story 2: domain rules (locked/available/complete; module status) unit-tested FIRST, then `GET /v1/modules/{id}/overview` + `GET /v1/learners/me/progress/{id}` per OA-MVP-007, then the screen | All 7 acceptance criteria of Story 2 + DoD (11 criteria, OA-MVP-010) |

Tasks 6 and 7 require no new decisions — their specs are complete in the docs
repo. Anything not listed here is out of scope (CLAUDE.md §3).

---

## 4. Standing references (docs repo — read before the relevant task)

| Task | Read first |
|---|---|
| Any session | `docs/directives/OA-DIR-001.md`, this handoff |
| PMV-002 | ADR-0006, OA-MVP-007 (Authentication section) |
| Step 2 | OA-MVP-006 (full schema), OA-MVP-004 (survey questions), `academy/learning-paths/digital-marketing/phase-0/module-1/*` (lesson content) |
| VS-002 | SPRINT-001 (Story 2 + tasks), OA-MVP-005 (domain rules), OA-MVP-007 (contracts), OA-MVP-010 (DoD) |

---

## 5. Open questions for the Product Owner

1. PMV-003: pin server to PHP 8.4 or amend decision to 8.5?
2. OA-MVP-006/007/010 statuses read "Draft — Pending Product Owner Approval"
   while functioning as approved references. Formally promote to Approved?
3. Confirm the browser URL used during smoke testing (needed for PMV-002 D1).

**END OF OA-HANDOFF-001**
