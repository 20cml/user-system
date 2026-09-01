---

description: "Task list for Mandatory Profile Completion"
---

# Tasks: Mandatory Profile Completion

**Input**: Design documents from `/specs/003-profile-completion/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/, quickstart.md (all present)

**Tests**: Included — the project constitution (Principle IV) requires automated test coverage for this feature's core flows.

**Organization**: Tasks are grouped by user story so each can be implemented and tested independently.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (US1, US2, US3)

## Phase 1: Setup

**Purpose**: Get the third-party address-lookup service ready to use

- [X] T001 Create a free Geoapify account and generate an API key (no credit card required — see research.md) — done, and verified end-to-end against the real API (a full address returns real Toronto results with the expected field shape)
- [X] T002 Add `GEOAPIFY_API_KEY=...` to `.env` (and `.env.example` with a blank placeholder) — real key in place

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Data and config every user story below depends on

**⚠️ CRITICAL**: Must be complete before any user story work begins

- [X] T003 Create and run a migration adding `phone`, `address_line`, `address_complement`, `city`, `region`, `postal_code`, `country`, `company_name`, and `profile_completed_at` (nullable) to the `users` table (`database/migrations/`, see data-model.md) — migration written; also added a matching `profile_completed_at` default to `UserFactory` (not originally planned) so the rest of the test suite doesn't break
- [X] T004 [P] Add the new fields to `app/Models/User.php`'s `#[Fillable]` attribute
- [X] T005 [P] Add a `geoapify` entry (reading `GEOAPIFY_API_KEY` from `.env`) to `config/services.php`

**Checkpoint**: Database and config ready — user stories can now be built.

---

## Phase 3: User Story 1 - Complete profile on first access (Priority: P1) 🎯 MVP

**Goal**: A user reaching an authenticated page for the first time (either sign-in method) is sent to `/profile` instead of the dashboard, and can only proceed once every required field is filled in manually.

**Independent Test**: Register (or sign up with Google), verify if applicable, confirm the dashboard is unreachable and `/profile` is shown instead; submit all required fields and confirm the dashboard becomes reachable.

### Tests for User Story 1 ⚠️

- [X] T006 [P] [US1] Write feature tests in `tests/Feature/ProfileCompletionTest.php` — covers: a user with an incomplete profile is redirected from `/dashboard` to `/profile`; submitting every required field unlocks `/dashboard`; a submission missing a required field is rejected with validation errors naming what's missing; the redirect behavior is identical whether the user authenticated via email/password or Google (FR-001, FR-002, FR-004)

### Implementation for User Story 1

- [X] T007 [US1] Create `app/Http/Middleware/EnsureProfileIsComplete.php` — redirects to `route('profile.edit')` when `$request->user()->profile_completed_at` is `null`, otherwise passes the request through (contracts/profile-routes.md)
- [X] T008 [US1] Register a `profile.complete` middleware alias in `bootstrap/app.php`, next to the existing `auth`/`guest`/`verified` aliases
- [X] T009 [US1] Add the `profile.complete` middleware to the `/dashboard` route's middleware list in `routes/web.php`, alongside `auth` and `verified` — explicitly do **not** add it to the `/profile` routes themselves (FR-001, FR-002)
- [X] T010 [US1] Update `app/Http/Requests/ProfileUpdateRequest.php` — require `phone`, `address_line`, `city`, `region`, `postal_code`, `company_name`, and `country` (restricted to `CA`/`US`); leave `address_complement` optional (FR-003, FR-004)
- [X] T011 [US1] Update `app/Http/Controllers/ProfileController.php`'s `update()` method — after saving, set `profile_completed_at` to now if every required field is present and it isn't already set (FR-008)
- [X] T012 [P] [US1] Update `resources/views/profile/edit.blade.php` — add the new required fields to the form with clear required-field indicators — done in `resources/views/profile/partials/update-profile-information-form.blade.php` (the actual location of the fields; `edit.blade.php` only includes the partial)

**Checkpoint**: `php artisan test --filter=ProfileCompletionTest` passes; User Story 1 is independently testable (address entry is fully manual at this point — that's expected until Phase 4).

---

## Phase 4: User Story 2 - Address autocomplete while completing the profile (Priority: P2)

**Goal**: Typing a Canadian or U.S. postal/zip code in the profile form offers a real address suggestion that fills in the rest of the address fields, without ever exposing the API key to the browser.

**Independent Test**: Type a known postal/zip code into the address field and confirm a suggestion appears and, once selected, fills the remaining fields (still editable); confirm the form still works with no suggestion selected.

### Tests for User Story 2 ⚠️

- [X] T013 [P] [US2] Write feature tests in `tests/Feature/AddressSuggestionTest.php` — covers: a valid query returns suggestions in the documented shape (mocked with `Http::fake()`, no real network call); a failed/timed-out third-party call returns an empty array with a 200, never an error (FR-005, FR-006, FR-007) — also covers empty query and missing API key short-circuiting before any HTTP call

### Implementation for User Story 2

- [X] T014 [US2] Create `app/Http/Controllers/Api/AddressSuggestionController.php` — accepts a query string, calls Geoapify server-side via the `Http` facade using the `config('services.geoapify.key')`, normalizes the response into the contract's shape, and catches failures by returning an empty array rather than an error (contracts/profile-routes.md)
- [X] T015 [US2] Add a `GET /api/address-suggestions` route in `routes/web.php`, under `auth` middleware only (no `profile.complete`, since this must work while the profile is still incomplete)
- [X] T016 [US2] Add vanilla-JS to `resources/views/profile/edit.blade.php` — calls the new endpoint as the user types in the postal/zip code field, renders a selectable suggestion, and fills `address_line`/`city`/`region`/`postal_code`/`country` on selection while keeping every field editable (FR-006) — done in the same partial as T012

**Checkpoint**: `php artisan test --filter=AddressSuggestion` passes; address entry is now assisted but manual entry (from Phase 3) still works if this phase were skipped or the API is unavailable.

---

## Phase 5: User Story 3 - Never see this screen again once complete (Priority: P2)

**Goal**: A user who has already completed their profile reaches the dashboard directly on every later login, and can still view/edit their profile afterward without the mandatory redirect firing again.

**Independent Test**: Complete the profile once, log out, log back in (either method), and confirm the dashboard is reached with no detour; then edit the profile voluntarily and confirm the dashboard is still reachable afterward.

### Tests for User Story 3 ⚠️

- [X] T017 [P] [US3] Write a feature test confirming a user with `profile_completed_at` already set reaches `/dashboard` directly after logging out and back in (both auth methods), and that voluntarily re-saving the profile via `/profile` afterward does not unset `profile_completed_at` (FR-008, FR-009)

### Implementation for User Story 3

- [X] T018 [US3] Confirm `ProfileController::update()` (from T011) only ever *sets* `profile_completed_at`, never clears it on a later save — confirmed by inspection (the `if ($user->profile_completed_at === null)` guard in T011 only ever transitions null → now(), never the reverse) and by T017's test; no code change needed

**Checkpoint**: `php artisan test --filter=ProfileCompletion` (all three stories) passes end-to-end.

---

## Phase 6: Polish & Cross-Cutting Concerns

- [X] T019 [P] Run the full suite (`php artisan test --filter=Profile`) and fix any failures — 12/12 passing; also ran the whole project suite (54/54 passing) since this feature touches shared infrastructure (`UserFactory`, `/dashboard`'s middleware); found and fixed a real regression in the pre-existing `tests/Feature/ProfileTest.php` (Breeze's default test), which submitted only `name`/`email` and now needed the new required fields too
- [X] T020 Walk through `quickstart.md`'s manual validation steps end-to-end in the browser, for both email/password and Google first-time sign-ins — verified both paths: register/verify or Google sign-in redirects to the profile page while incomplete, address autocomplete works with the real Geoapify key, and the dashboard unlocks once the profile is saved
- [X] T021 [P] Re-read all touched files against the constitution's Simplicity and Explainable Code principles; simplify anything that isn't easy to explain — reviewed, no changes needed

---

## Dependencies & Execution Order

- **Setup (Phase 1)**: No dependencies — start immediately.
- **Foundational (Phase 2)**: Depends on Setup — blocks every user story.
- **User Story 1 (Phase 3)**: Depends only on Foundational. Independently testable and deployable as the MVP (manual address entry).
- **User Story 2 (Phase 4)**: Depends only on Foundational, but is only meaningful once the profile form exists (in practice, done after US1).
- **User Story 3 (Phase 5)**: Depends on Foundational and on US1's `profile_completed_at` logic existing (T011) to have anything to verify.
- **Polish (Phase 6)**: Depends on all three user stories being complete.

### Parallel Opportunities

- T001, T002 (Setup) can run in sequence quickly, or T002 in parallel once the key from T001 exists.
- T004, T005 (Foundational) touch different files and can run in parallel once T003's migration is written.
- T012 (US1 view) can run in parallel with T007–T011 since it touches a different file.
- T013 (US2 test) and T017 (US3 test) can be written in parallel with other work in their phases since they touch different files.

---

## Implementation Strategy

### MVP First

Phase 3 (User Story 1) alone is independently testable and demonstrable — the mandatory gate
works end-to-end with fully manual address entry. Phase 4 (autocomplete) and Phase 5 (never
repeat) are valuable but not required for the gate itself to function, so US1 is the true MVP
here, unlike Feature 1 where all three stories had to chain together before the feature was usable
at all.

### Incremental Delivery

1. Setup + Foundational → environment and schema ready
2. User Story 1 → the gate works, manual entry only (MVP)
3. User Story 2 → address entry gets faster and less error-prone
4. User Story 3 → confirmed the gate never repeats once satisfied
5. Polish → full suite green, manual quickstart walkthrough in the browser for both auth methods
