# Implementation Plan: Google Login

**Branch**: `002-google-login` | **Date**: 2026-08-28 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/002-google-login/spec.md`

## Summary

Visitors can register or log in via Google's OAuth consent flow. A new Google identity with no
matching email creates an already-verified account; a Google identity matching an existing
email/password account links to it (verifying it if it wasn't already). Built with Laravel
Socialite against Google's OAuth2 provider, storing a `google_id` on the existing `User` model.

## Technical Context

**Language/Version**: PHP 8.3+, Laravel 13 (already installed)

**Primary Dependencies**: `laravel/socialite` (per constitution) — no other new packages needed.

**Storage**: MySQL. A new migration adds a nullable, unique `google_id` column to the existing
`users` table and makes the existing `password` column nullable (for Google-only accounts, which
have no password — see research.md). The Feature 1 migration file itself is untouched.

**Testing**: PHPUnit Feature tests, mocking the `Socialite` facade (Mockery-based, e.g.
`Socialite::shouldReceive('driver->user')->andReturn(...)`) so tests never call Google's real
servers — this is Socialite's own documented/idiomatic testing approach.

**Target Platform**: Web (same server-rendered Blade app as the email/password feature)

**Project Type**: Single Laravel project (same monolith, no new option)

**Performance Goals**: No special requirements beyond the round-trip to Google's OAuth servers,
which is inherently network-bound and outside this app's control.

**Constraints**: Google OAuth client ID/secret live only in `.env` (never committed); Socialite's
default *stateful* web flow is used, which includes CSRF-equivalent `state` parameter protection
against forged callbacks — no custom OAuth security code is written.

**Scale/Scope**: Same small learning-project scope as Feature 1.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Check | Status |
|---|---|---|
| I. Simplicity & Convention over Configuration | Uses Socialite's standard driver and stateful web flow — no custom OAuth handling | PASS |
| II. Security by Default | Client secret only in `.env`; Socialite's built-in CSRF-equivalent `state` check; Google-provided email trusted only because Google itself verifies it | PASS |
| III. Spec-Driven Development | Follows spec.md; each stage committed | PASS |
| IV. Test Coverage for Core Flows | Feature tests planned for new-account, returning-user, and account-linking paths | PASS |
| V. Explainable Code | Socialite is a thin, well-documented wrapper; no custom OAuth protocol code | PASS |
| Technology Constraints | Laravel Socialite, as named in the constitution | PASS |

No violations — Complexity Tracking table is not needed for this feature.

## Project Structure

### Documentation (this feature)

```text
specs/002-google-login/
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
└── Http/
    └── Controllers/
        └── Auth/
            └── GoogleAuthController.php   # redirect() + callback() actions

config/
└── services.php          # add a 'google' credentials block

database/migrations/
└── xxxx_xx_xx_add_google_id_to_users_table.php   # new migration: nullable+unique google_id, password made nullable

routes/
└── auth.php               # add GET /auth/google/redirect and /auth/google/callback

resources/views/auth/
├── login.blade.php        # add "Continue with Google" button
└── register.blade.php     # add "Continue with Google" button

tests/Feature/Auth/
└── GoogleAuthenticationTest.php
```

**Structure Decision**: Same single Laravel project as Feature 1 — this feature only adds one
controller, one migration, one config block, and two routes to the existing structure.

## Complexity Tracking

*No constitution violations — table intentionally left empty.*
