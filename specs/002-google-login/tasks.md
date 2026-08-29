---

description: "Task list for Google Login"
---

# Tasks: Google Login

**Input**: Design documents from `/specs/002-google-login/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/, quickstart.md (all present). Depends on Feature 1 (email/password authentication) being implemented.

**Tests**: Included — the project constitution (Principle IV) requires automated test coverage for this feature's core flows.

**Organization**: Tasks are grouped by user story so each can be implemented and tested independently.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (US1, US2, US3)

## Phase 1: Setup

- [X] T001 Install Laravel Socialite (`composer require laravel/socialite`)
- [X] T002 [P] Create a Google Cloud OAuth 2.0 Client ID and add `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI` to `.env`
- [X] T003 [P] Add a `google` credentials block (reading those `.env` values) to `config/services.php`

---

## Phase 2: Foundational (Blocking Prerequisites)

**⚠️ CRITICAL**: Must be complete before any user story work begins

- [X] T004 Create a migration adding a nullable, unique `google_id` column to `users` and making the existing `password` column nullable (per data-model.md / research.md)
- [X] T005 Run `php artisan migrate`
- [X] T006 Add `google_id` to `app/Models/User.php`'s fillable attributes

**Checkpoint**: Schema and dependencies ready — user stories can now be built.

---

## Phase 3: User Story 1 - Register with Google for the first time (Priority: P1) 🎯 MVP

**Goal**: A new visitor can create an already-verified account via Google's consent flow, with no separate email verification step.

**Independent Test**: Complete the Google consent flow (mocked in tests) with an email not already in the system; confirm a new, verified account is created and the visitor is logged in.

- [X] T007 [US1] Write feature tests in `tests/Feature/Auth/GoogleAuthenticationTest.php` — covers: a mocked new Google identity creates a verified account and logs the visitor in; a cancelled/denied consent creates no account and returns to `/login` with an error
- [X] T008 [US1] Create `app/Http/Controllers/Auth/GoogleAuthController.php` with a `redirect()` method sending the visitor to Google's consent screen
- [X] T009 [US1] Implement `callback()` on `GoogleAuthController.php`: resolve the account (create new-and-verified, or link/return an existing one by email/google_id — full logic per data-model.md, exercised further by US2/US3 below), start the session, handle a cancelled/denied consent gracefully (FR-001 through FR-007)
- [X] T010 [US1] Add `GET /auth/google/redirect` and `GET /auth/google/callback` routes (guest-only group) to `routes/auth.php`
- [X] T011 [P] [US1] Add a "Continue with Google" button/link to `resources/views/auth/login.blade.php` and `resources/views/auth/register.blade.php`

**Checkpoint**: `php artisan test --filter=GoogleAuthenticationTest` passes for new-account creation; User Story 1 is independently testable.

---

## Phase 4: User Story 2 - Log in again with Google (Priority: P1)

**Goal**: A returning Google-authenticated visitor is recognized as the same user, never duplicated.

**Independent Test**: Complete the (mocked) Google consent flow twice with the same Google identity; confirm both times resolve to the same user id.

- [X] T012 [US2] Add feature tests in `GoogleAuthenticationTest.php` confirming a second sign-in with the same Google identity (matching `google_id`) logs into the same existing account rather than creating a duplicate (FR-004) — exercises the `callback()` logic already built in T009, no new implementation

**Checkpoint**: Repeat Google sign-ins verified to land on one account.

---

## Phase 5: User Story 3 - Link an existing email/password account (Priority: P2)

**Goal**: Signing in with Google using an email that already has a password-based account links to (and verifies) that same account, rather than creating a second one.

**Independent Test**: Register via `/register`, then complete the (mocked) Google consent flow with the same email; confirm the result is the same user id, now verified and `google_id`-linked.

- [X] T013 [US3] Add feature tests in `GoogleAuthenticationTest.php` confirming Google sign-in with an email matching an existing (verified or unverified) email/password account links `google_id` onto that account and marks it verified (FR-005) — exercises the `callback()` logic already built in T009, no new implementation

**Checkpoint**: All three user stories independently functional and testable together.

---

## Phase 6: Polish & Cross-Cutting Concerns

- [ ] T014 [P] Run the full suite (`php artisan test --filter=Auth`) and fix any failures
- [ ] T015 Walk through `quickstart.md`'s manual validation steps with a real Google account in the browser
- [ ] T016 [P] Re-read all touched files against the constitution's Simplicity and Explainable Code principles

---

## Dependencies & Execution Order

- **Setup (Phase 1)**: No dependencies — start immediately.
- **Foundational (Phase 2)**: Depends on Setup — blocks every user story.
- **User Story 1 (Phase 3)**: Depends only on Foundational. Builds the core `callback()` logic.
- **User Story 2 (Phase 4)** and **User Story 3 (Phase 5)**: Both depend on User Story 1's `callback()` implementation already existing — they add tests proving branches of that same logic, not new code. List order (US2 before US3) follows spec priority (P1 before P2), but either could be verified first.
- **Polish (Phase 6)**: Depends on all three user stories being complete.

### Parallel Opportunities

- T002, T003 (Setup) can run in parallel.
- T011 (view changes) can run in parallel with T009/T010 (different files).

---

## Implementation Strategy

### MVP First

Phase 3 (User Story 1) delivers a working "register via Google" path and is the MVP. However,
because the account-resolution logic in T009 is written once and covers all three stories'
scenarios together, User Stories 2 and 3 are best done immediately after as verification passes
over that same code, not as separate build phases.

### Incremental Delivery

1. Setup + Foundational → environment and schema ready
2. User Story 1 → Google registration + full account-resolution logic works
3. User Story 2 → confirmed: repeat sign-ins don't duplicate accounts
4. User Story 3 → confirmed: linking to an existing email/password account works
5. Polish → full suite green, manual quickstart walkthrough with a real Google account
