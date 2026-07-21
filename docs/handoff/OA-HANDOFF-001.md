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

### 1.3 Session zero verification results (2026-07-21)

- "11 passed / 52 assertions" for `php artisan test` on the server —
  **[PARTIALLY VERIFIED]**. Reproducible only when the shell env forces
  `DB_CONNECTION=pgsql` against the live Postgres service (matching CI's
  job-level `env:` override). The bare documented command
  (`cd backend && php artisan test`, equivalent to `make test`) fails 10/11 on
  this server: `phpunit.xml` hardcodes `DB_CONNECTION=sqlite` / `:memory:`,
  and the `learners`/`sessions` migration uses Postgres-only defaults
  (`gen_random_uuid()`, `now()`) that SQLite rejects. Backlogged; fix deferred
  to work-order Task 5 (remove the hardcoded DB env from `phpunit.xml`).
- `git tag platform-foundation-v1` pushed — **[DISPROVEN]**. `git tag -l`
  (local) and `gh api repos/oravil/oravil-academy-platform/tags` (remote) both
  return empty. Tag will be cut at work-order Task 5, after the PMV-002 smoke
  test closes.
- CI actually green on `main` at the latest commit — **[VERIFIED]**. Run
  `29855291930` for commit `cb9d416`: `completed` / `success`.

### 1.4 Not built yet [PLANNED]

- Content tables (8): `learning_paths`, `phases`, `modules`, `lessons`,
  `assignments`, `submissions`, `surveys`, `survey_questions` — no migrations.
- Step 2 Content Seeding; VS-002 Module Overview; Steps 5-9 of OA-MVP-010.

### 1.5 Completion estimate

~35% toward v0.1 (Steps 1 and 3 of nine done in code, weighted for
foundation/tooling/governance; smoke test still open — not rounded up).

---

## 2. Open problems (the entire current work queue)

### PMV-002 — Browser session persistence bug [FIXED-VERIFIED] — TOP PRIORITY

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

**Resolution (2026-07-21) — [FIXED-UNVERIFIED], pending Product Owner browser confirmation:**

D1: browser URL confirmed as `http://localhost:5173` — no host/CORS mismatch,
S1 **excluded** (matches every allowlist; `SANCTUM_STATEFUL_DOMAINS`,
`CORS_ALLOWED_ORIGINS` both already cover this origin).

D2-D3: `.env`/`config:show` confirmed no stateful-domain or session-driver
misconfiguration.

D4-D5 (curl-reproduced, since no live browser DevTools session was available):
clean isolated repro with `sessions` table truncated, login via
`/sanctum/csrf-cookie` → `POST /v1/auth/login` (200), then 5 consecutive
`GET /v1/auth/me` calls reusing the same cookie jar, matching D1's exact
origin. Pre-fix: refresh 1 → 200, refreshes 2-5 → 401; `sessions` table showed
1 authenticated row + 3 anonymous rows created within the same request cycle —
a direct match for the documented symptom.

**Verified root cause: S2 (middleware duplication).** `routes/api.php` wrapped
`/auth/*` in `Route::middleware('web')` while `bootstrap/app.php`'s
`statefulApi()` already applies the equivalent stateful pipeline via
`EnsureFrontendRequestsAreStateful`. Session middleware (`StartSession`,
`EncryptCookies`) ran twice per request; on the first authenticated request
after login, the duplicated pass failed to recognize the session and minted a
fresh anonymous session, silently overwriting the authenticated cookie in the
`Set-Cookie` response header. S3 was not needed to explain the symptom.

**Fix applied (commit `e4ce4df`):** removed the redundant
`Route::middleware('web')` wrapper from `routes/api.php`; `statefulApi()`
already supplies session/cookie/CSRF handling for these routes.

**Post-fix verification (curl, matching D1 origin exactly):**
- `csrf-cookie` → `login` → 200, `Set-Cookie` present (XSRF-TOKEN + session)
- 5 consecutive `/v1/auth/me` calls → all 200, same learner returned each time
- `sessions` table after 5 refreshes: **1 row, authenticated, no anonymous
  rows created**
- `logout` → 204; `/me` after logout → 401 `unauthenticated`
- CSRF negative path: missing `X-XSRF-TOKEN` → 419; invalid token → 419, both
  with the approved `CSRF_TOKEN_MISMATCH` error envelope
- `cd backend && php artisan test` (env-forced pgsql) → **11 passed, 52
  assertions** (3 tests in `AuthenticationTest.php` required adding a
  `Referer: http://localhost:5173` header to keep exercising the real
  stateful path — `Sanctum::fromFrontend()` gates session middleware on
  `Referer`/`Origin` matching `SANCTUM_STATEFUL_DOMAINS`, which the test
  client does not send by default. Route fix was not reverted; no `web` group
  was re-added anywhere.)
- `cd frontend && pnpm test` → **3 files, 11 tests, all passed**

**Status: FIXED-VERIFIED (2026-07-21).** Product Owner independently confirmed
via live browser testing at `http://localhost:5173`: multiple consecutive
refreshes — more than the 5-refresh minimum exit criterion — all preserved
the authenticated session (`GET /v1/auth/me` consistently 200, protected
route rendered correctly, no unexpected logout or anonymous session reset).

**Browser verification script (Product Owner — manual steps):**
1. Open DevTools → Network tab (keep open), Application/Storage tab → Cookies
   for `localhost:5173`. Clear cookies for both `localhost:5173` and
   `localhost:8000` first (or use a fresh Incognito window).
2. Navigate to `http://localhost:5173`, log in with the seeded test learner
   (`test@example.com` / `password`).
3. In Network tab, find the `POST /v1/auth/login` request → confirm response
   headers include two `Set-Cookie` entries (`XSRF-TOKEN`, session cookie) and
   status 200.
4. Refresh the page (F5) **5 times**. Each time, find the `GET /v1/auth/me`
   (or equivalent) request in Network tab → confirm status 200 each time, and
   that the learner stays logged in (no bounce to login screen).
5. In Application/Storage → Cookies, confirm the session cookie's value stays
   associated with the same session (visually: it may still rotate ciphertext
   per response — that's expected IV randomization — the important check is
   you never get logged out).
6. Click Logout → confirm redirect/UI shows logged-out state. Try navigating
   to an authenticated route or reloading → confirm you're treated as logged
   out (401 in Network tab for `/me`, or redirect to login).
7. Report back: which steps passed/failed, and paste/screenshot any
   unexpected status codes from the Network tab.

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

### PMV-003 — Server CLI on PHP 8.5.4 vs approved 8.4 [RESOLVED — 2026-07-21]

Product Owner initially chose option (a) — pin server to 8.4 via
`update-alternatives`. Execution found no PHP 8.4 package installable on
this server's OS (Ubuntu 26.04 "resolute"): no apt candidate, and the
`ondrej/php` PPA has no published package index for this release
(confirmed `404 Not Found` on its `Release` file; the PPA's own
description confirms it is being merged into `packages.sury.org/php` and
will not serve new Ubuntu releases going forward). Building from source or
forcing an older codename's packages onto a newer base OS were considered
and rejected as disproportionate.

Product Owner then chose option (b): amend the approved decision to 8.5.
Docs-first per project convention: **ADR-0007** (docs repo) records the
amendment. Code aligned to match: `composer.json` (`^8.4` → `^8.5`,
`composer.lock` content-hash refreshed via `composer update --lock`, no
dependency versions changed), CI's `setup-php` step (8.4 → 8.5), and
`CLAUDE.md` §2. Server CLI already ran 8.5.4, so no server-side change was
required once the decision was amended. Verified: `php -v` → 8.5.4;
`php artisan test` against an isolated `oravil_academy_test` database
(not the live dev DB, per the standing interim rule) → 11 passed, 52
assertions. Commit: `ca9418c`.

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
| — | **Note (2026-07-21):** `platform-foundation-v1` was already cut at commit `10e6506`, slightly ahead of this task, once the PMV-002 smoke test closed (Product Owner call — tags don't need to chase every subsequent commit). When Task 5 itself completes, cut a **new** tag (e.g. `platform-foundation-v1.1`, exact name TBD) to mark that later, more complete Foundation milestone. Do not move or force-push the existing tag. | — |
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

1. ~~PMV-003: pin server to PHP 8.4 or amend decision to 8.5?~~ **RESOLVED
   2026-07-21:** initial call was pin to 8.4; execution found 8.4 is not
   apt-installable on this server's OS, so the decision was re-amended to
   8.5 (ADR-0007). See PMV-003 in §2 for the full evidence chain.
2. OA-MVP-006/007/010 statuses read "Draft — Pending Product Owner Approval"
   while functioning as approved references. Formally promote to Approved?
3. ~~Confirm the browser URL used during smoke testing (needed for PMV-002
   D1).~~ **RESOLVED 2026-07-21:** `http://localhost:5173`.
4. **Backlog (2026-07-21):** What is the actual provisioning process for
   real pilot learners at launch time? ADR-0005 mandates manual provisioning
   with no self-registration, but no document defines the mechanism
   (artisan command? manual DB insert? admin script?). Product Owner
   decision needed before launch, not before Task 6/7.

**END OF OA-HANDOFF-001**
