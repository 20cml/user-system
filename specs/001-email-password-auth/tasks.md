---

description: "Task list for Email & Password Authentication"
---

# Tasks: Email & Password Authentication

**Input**: Design documents from `/specs/001-email-password-auth/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/, quickstart.md (all present)

**Tests**: Included — the project constitution (Principle IV) requires automated test coverage for this feature's core flows.

**Organization**: Tasks are grouped by user story so each can be implemented and tested independently.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (US1, US2, US3)

## Phase 1: Setup

**Purpose**: Get the project environment ready for this feature

- [X] T001 Install Laravel Breeze and scaffold Blade auth views (`composer require laravel/breeze --dev` then `php artisan breeze:install blade`)
- [X] T002 [P] Set up a local MySQL server and create the project database
- [X] T003 [P] Configure `.env`: `DB_CONNECTION=mysql` with matching credentials, and `MAIL_MAILER=log` for local development
- [X] T004 [P] Install and build frontend assets (`npm install && npm run build`)

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Infrastructure every user story below depends on

**⚠️ CRITICAL**: Must be complete before any user story work begins

- [X] T005 Run `php artisan migrate` against MySQL to create the `users` table (uses the existing `database/migrations/0001_01_01_000000_create_users_table.php` — no new migration needed per data-model.md)
- [X] T006 Make `app/Models/User.php` implement `MustVerifyEmail` so Laravel's email verification system (signed links, `Verified` event) is active

**Checkpoint**: Database and email-verification support ready — user stories can now be built.

---

## Phase 3: User Story 1 - Register for a new account (Priority: P1) 🎯 MVP

**Goal**: A visitor can create an account with an email and password; passwords are hashed; a duplicate verified email is rejected, a duplicate unverified email gets a fresh verification link instead.

**Independent Test**: Submit the registration form with a new email/password and confirm an unverified account is created with a hashed password.

- [X] T007 [US1] Write feature tests for registration in `tests/Feature/Auth/RegistrationTest.php` — covers: new unverified account created with hashed password; registering with an already-**verified** email is rejected; registering with an already-**unverified** email resends verification instead of creating a duplicate (FR-002); weak password (<8 chars) is rejected
- [X] T008 [US1] Update `app/Http/Controllers/Auth/RegisteredUserController.php`: on a duplicate email, check verification status — resend the verification notification for unverified accounts instead of failing uniqueness validation (FR-002)
- [X] T009 [US1] Confirm registration validation rules (valid email format, minimum 8-character password) in `app/Http/Controllers/Auth/RegisteredUserController.php` (FR-002, FR-003)
- [X] T010 [P] [US1] Review `resources/views/auth/register.blade.php` against the spec's acceptance scenarios

**Checkpoint**: `php artisan test --filter=RegistrationTest` passes; User Story 1 is independently testable.

---

## Phase 4: User Story 2 - Verify email address (Priority: P1)

**Goal**: A user confirms ownership of their email via a signed link before they can access anything beyond the "verify your email" prompt.

**Independent Test**: Register an account, retrieve the verification link, confirm clicking it marks the account verified and unlocks access; confirm an unverified session is redirected to the verify-email prompt from any other page.

- [X] T011 [US2] Write feature tests for email verification in `tests/Feature/Auth/EmailVerificationTest.php` — covers: valid link verifies the account and fires the `Verified` event; an unverified session is redirected away from protected pages to `/verify-email`; resend works and is rate-limited; an invalid/expired link is rejected
- [X] T012 [US2] Apply the `verified` middleware to the app's authenticated route group in `routes/web.php` so an unverified session cannot reach anything but `/verify-email` (FR-009) — already present on `/dashboard` via Breeze's default scaffold; confirmed by test, no change needed
- [X] T013 [P] [US2] Confirm `app/Http/Controllers/Auth/VerifyEmailController.php` marks `email_verified_at` and redirects correctly (FR-009, FR-012) — confirmed, no change needed
- [X] T014 [P] [US2] Confirm `app/Http/Controllers/Auth/EmailVerificationNotificationController.php` resend logic and rate limiting (FR-011) — confirmed, no change needed
- [X] T015 [P] [US2] Update `resources/views/auth/verify-email.blade.php` copy to match the spec's wording — already matches, no change needed

**Checkpoint**: `php artisan test --filter=EmailVerificationTest` passes; the full register → verify path works end-to-end.

---

## Phase 5: User Story 3 - Log in with a verified account (Priority: P2)

**Goal**: A verified user can log in with email/password and stays authenticated until logout; failed attempts are rejected generically and rate-limited.

**Independent Test**: Log in with a verified account's correct credentials and confirm an authenticated session starts and persists across requests.

- [X] T016 [US3] Write feature tests for login in `tests/Feature/Auth/AuthenticationTest.php` — covers: verified user logs in successfully; wrong password rejected with a generic error; nonexistent email rejected with the same generic error (FR-006); repeated failed attempts get rate-limited (FR-008); session persists across requests
- [X] T017 [US3] Confirm `app/Http/Controllers/Auth/AuthenticatedSessionController.php` returns one generic error message for any failed login, never revealing which field was wrong (FR-006) — confirmed via `LoginRequest`, no change needed
- [X] T018 [US3] Confirm Breeze's built-in login rate limiting is active in `AuthenticatedSessionController.php` (FR-008) — confirmed via `LoginRequest` (5 attempts, then throttled), no change needed
- [X] T019 [P] [US3] Review `resources/views/auth/login.blade.php` against the spec's acceptance scenarios — already matches, no change needed

**Checkpoint**: `php artisan test --filter=AuthenticationTest` passes; all three user stories work independently and together.

---

## Phase 6: Polish & Cross-Cutting Concerns

- [X] T020 [P] Run the full suite (`php artisan test --filter=Auth`) and fix any failures — 30/30 passing
- [X] T021 Walk through `quickstart.md`'s manual validation steps end-to-end in the browser — verified in a real browser: register → verify email link → dashboard; logout → login again without re-verification; duplicate registration correctly rejected
- [X] T022 [P] Re-read all touched files against the constitution's Simplicity and Explainable Code principles; simplify anything that isn't easy to explain — reviewed, no changes needed

---

## Dependencies & Execution Order

- **Setup (Phase 1)**: No dependencies — start immediately.
- **Foundational (Phase 2)**: Depends on Setup — blocks every user story.
- **User Story 1 (Phase 3)**: Depends only on Foundational. Independently testable on its own.
- **User Story 2 (Phase 4)**: Depends only on Foundational, but is only meaningful once accounts exist to verify (in practice, done after US1).
- **User Story 3 (Phase 5)**: Depends on Foundational; logging in only succeeds once an account exists **and** is verified (US1 + US2), even though the login code itself doesn't depend on their implementation.
- **Polish (Phase 6)**: Depends on all three user stories being complete.

### Parallel Opportunities

- T002, T003, T004 (Setup) can run in parallel.
- T013, T014, T015 (US2) touch different files and can run in parallel once T011/T012 are done.
- T010 (US1) and T019 (US3) — view review tasks — can run in parallel with other work in their phase since they touch different files.

---

## Implementation Strategy

### MVP First

Phase 3 (User Story 1) alone is independently testable and demonstrable — a working registration
flow. But note this feature only becomes *usable end-to-end* (someone actually logging in) once
User Story 2 and User Story 3 are also done, since both are P1/P2 and chain together
(register → verify → log in). Recommended order: complete all three phases in sequence
(3 → 4 → 5) rather than treating US1 as a standalone deployable increment.

### Incremental Delivery

1. Setup + Foundational → environment ready
2. User Story 1 → registration works, testable on its own
3. User Story 2 → verification works, register→verify path testable end-to-end
4. User Story 3 → login works, the full feature is usable
5. Polish → full suite green, manual quickstart walkthrough confirms it in the browser
