# Implementation Plan: Email & Password Authentication

**Branch**: `001-email-password-auth` | **Date**: 2026-08-26 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/001-email-password-auth/spec.md`

## Summary

Users can register an account with an email and password, must verify their email
via a confirmation link before they can log in, and can then log in to start an
authenticated session. Built on Laravel Breeze's authentication scaffolding
(Blade views, session-based auth) with Laravel's built-in email verification
support, storing users in MySQL with bcrypt-hashed passwords.

## Technical Context

**Language/Version**: PHP 8.3+, Laravel 13 (already installed in this repo)

**Primary Dependencies**: `laravel/breeze` (auth scaffolding: controllers, routes, Blade views), Laravel's built-in `Illuminate\Auth` (hashing, sessions, rate limiting) and `MustVerifyEmail` / `VerifyEmail` notification (email verification) — no new third-party auth packages needed for this feature.

**Storage**: MySQL (per constitution). Single `users` table — the default Laravel migration already includes the `email_verified_at` column this feature needs.

**Testing**: PHPUnit (already in `composer.json`) using Laravel's `RefreshDatabase` Feature tests against an in-memory SQLite database for speed; MySQL remains the app's storage engine for local/dev/prod.

**Target Platform**: Web (server-rendered Blade app, session-based auth)

**Project Type**: Single Laravel project (monolith) — Option 1 below

**Performance Goals**: No special scaling requirements; standard single-server Laravel monolith performance is sufficient for this learning project.

**Constraints**: Passwords hashed via Laravel's default `Hash` facade (bcrypt); CSRF protection on all forms (Laravel default); login attempts rate-limited via Laravel's built-in throttle; verification links are Laravel signed URLs with expiration (built-in signed-route mechanism), so no custom token table is needed.

**Scale/Scope**: Small learning project; not expected to handle production-scale concurrent traffic.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Check | Status |
|---|---|---|
| I. Simplicity & Convention over Configuration | Uses Breeze's stock controllers/views and Laravel's built-in email verification — no custom abstractions added | PASS |
| II. Security by Default | Bcrypt hashing, CSRF, server-side validation, rate-limited login, signed+expiring verification links | PASS |
| III. Spec-Driven Development | This plan follows spec.md; each stage will be committed | PASS |
| IV. Test Coverage for Core Flows | Feature tests planned for registration, verification, login (see quickstart.md / tasks.md) | PASS |
| V. Explainable Code | Breeze's generated code is standard, well-documented Laravel — no unexplained "magic" | PASS |
| Technology Constraints | Laravel + MySQL + Breeze, no extra frameworks | PASS |

No violations — Complexity Tracking table is not needed for this feature.

## Project Structure

### Documentation (this feature)

```text
specs/001-email-password-auth/
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
│   └── Controllers/
│       └── Auth/
│           ├── RegisteredUserController.php       # handles registration form + submission
│           ├── AuthenticatedSessionController.php # handles login form + submission + logout
│           ├── EmailVerificationPromptController.php
│           ├── VerifyEmailController.php           # handles the signed verification link
│           └── EmailVerificationNotificationController.php # resend verification email
└── Models/
    └── User.php                                    # implements MustVerifyEmail

routes/
├── web.php
└── auth.php             # Breeze convention: dedicated auth routes file

resources/views/auth/
├── register.blade.php
├── login.blade.php
└── verify-email.blade.php

database/migrations/
└── 0001_01_01_000000_create_users_table.php  # already exists; includes email_verified_at

tests/Feature/Auth/
├── RegistrationTest.php
├── AuthenticationTest.php
└── EmailVerificationTest.php
```

**Structure Decision**: Single Laravel project (monolith), no separate frontend/backend — this is
a server-rendered Blade application using Laravel Breeze's standard `Auth/` controller and
`auth/` view conventions, so the paths above are real project paths, not placeholders.

## Complexity Tracking

*No constitution violations — table intentionally left empty.*
