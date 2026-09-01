# Implementation Plan: Mandatory Profile Completion

**Branch**: `003-profile-completion` | **Date**: 2026-08-31 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/003-profile-completion/spec.md`

## Summary

The first time a user reaches an authenticated area — whether they registered with
email/password (and verified it) or signed up with Google — they must fill in phone number,
address, and company name before the dashboard becomes reachable. The address portion offers a
live suggestion as the user types their postal/zip code (Canada and the U.S. only, via a
third-party address-lookup API), but manual entry always remains valid. Once saved, the account
is marked profile-complete and is never sent back to this screen again. Enforced via a new route
middleware on the protected route group — mirroring how the existing `verified` middleware
already gates `/dashboard` — so none of the existing registration, login, Google, or
email-verification code changes.

## Technical Context

**Language/Version**: PHP 8.3+, Laravel 13 (already installed in this repo)

**Primary Dependencies**: No new Composer packages. Uses Laravel's built-in `Http` facade to call
the address-lookup API server-side (keeps the API key off the client), Laravel's route middleware
system, and Eloquent for the new `users` columns. A small vanilla-JS snippet on the profile Blade
view calls a first-party endpoint (not the third-party API directly) to fetch suggestions as the
user types — no frontend framework/library added.

**Storage**: MySQL (per constitution). Same `users` table, extended with new nullable-until-filled
columns via a new migration.

**Testing**: PHPUnit Feature tests (`RefreshDatabase`), matching 001/002's pattern. Calls to the
third-party address API are mocked with Laravel's `Http::fake()` so tests never depend on network
access or the real service's availability/quota.

**Target Platform**: Web (same server-rendered Blade app, session-based auth)

**Project Type**: Single Laravel project (monolith) — same as 001/002, Option 1 below

**Performance Goals**: No special scaling requirements — same learning-project scale as 001/002.

**Constraints**: Address-lookup API key stored in `.env` / `config/services.php`, never committed
(constitution Principle II). The chosen provider's free tier (3,000 requests/day, no credit card)
must be enough for this project's scale. Suggestions must degrade gracefully to fully manual entry
if the API is slow, over quota, or returns nothing (spec FR-007) — the profile form must never be
blocked by the third-party service being unavailable.

**Scale/Scope**: Small learning project; not expected to approach the free-tier request quota.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Check | Status |
|---|---|---|
| I. Simplicity & Convention over Configuration | Standard Laravel middleware + `Http` facade; no new package, no custom auth abstraction | PASS |
| II. Security by Default | API key server-side only (never exposed to the browser), CSRF on the profile form (Laravel default), all fields validated server-side | PASS |
| III. Spec-Driven Development | This plan follows spec.md; each stage committed | PASS |
| IV. Test Coverage for Core Flows | Feature tests planned for the profile-completion gate (one of the constitution's five required core flows — "updating profile" — plus the new redirect behavior) | PASS |
| V. Explainable Code | Middleware mirrors the already-explainable `verified` pattern; no unexplained magic | PASS |
| Technology Constraints | Laravel + MySQL + Breeze conventions kept; the one external dependency (address-lookup API) is directly justified by spec FR-005/FR-006 | PASS |

No violations — Complexity Tracking table is not needed for this feature.

## Project Structure

### Documentation (this feature)

```text
specs/003-profile-completion/
├── plan.md              # This file (/speckit-plan command output)
├── research.md          # Phase 0 output (/speckit-plan command)
├── data-model.md        # Phase 1 output (/speckit-plan command)
├── quickstart.md        # Phase 1 output (/speckit-plan command)
├── contracts/           # Phase 1 output (/speckit-plan command)
└── tasks.md             # Phase 2 output (/speckit-tasks command - NOT created by /speckit-plan)
```

### Source Code (repository root)

```text
app/
├── Http/
│   ├── Controllers/
│   │   ├── ProfileController.php               # extended: new fields, marks profile complete
│   │   └── Api/
│   │       └── AddressSuggestionController.php  # NEW: server-side proxy to the address API
│   ├── Middleware/
│   │   └── EnsureProfileIsComplete.php          # NEW: gates protected routes
│   └── Requests/
│       └── ProfileUpdateRequest.php             # extended: new required-field rules
└── Models/
    └── User.php                                 # new fillable fields + profile_completed_at

routes/
└── web.php               # /dashboard (and other protected routes) gain the new middleware;
                           # one new lightweight route for the address-suggestion proxy

resources/views/profile/
└── edit.blade.php        # new fields + address-suggestion JS

database/migrations/
└── [timestamp]_add_profile_fields_to_users_table.php   # NEW

config/
└── services.php          # address-lookup API key/config entry

tests/Feature/
└── ProfileCompletionTest.php   # NEW
```

**Structure Decision**: Single Laravel project (monolith), same as 001/002 — no new project type,
no separate frontend. The one addition to the existing shape is a thin first-party API route
(`Api/AddressSuggestionController.php`) that proxies to the third-party service, so the API key
never reaches the browser.

## Complexity Tracking

*No constitution violations — table intentionally left empty.*
