---

description: "Task list for Property Listings"
---

# Tasks: Property Listings

**Input**: Design documents from `/specs/004-property-listings/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/, quickstart.md (all present)

**Tests**: Included — the project constitution (Principle IV) requires automated test coverage for this feature's core flows.

**Organization**: Tasks are grouped by user story so each can be implemented and tested independently.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (US1, US2, US3, US4, US5)

## Phase 1: Setup

**Purpose**: Base data layer every story depends on

- [X] T001 Create the `listings` migration in `database/migrations/` — all fields from data-model.md (`user_id`, `source`, `listing_type`, `property_type`, `status` default `available`, `address_line`, `city`, `state_province`, `postal_code`, `country`, `price`, `currency`, `area_sqm`, `description`, `photo_path`)
- [X] T002 [P] Create `app/Models/Listing.php` — fillable fields, `price`/`area_sqm` cast to `decimal`, `belongsTo(User::class)`
- [X] T003 [P] Add a `listings()` `hasMany(Listing::class)` relationship to `app/Models/User.php`
- [X] T004 [P] Create `database/factories/ListingFactory.php` with realistic fake data for every field — also added `country` to `UserFactory` (not originally planned), since Listing's `currency` depends on `auth()->user()->country` and the factory didn't set it
- [X] T008 Run `php artisan storage:link` (if not already linked) so an uploaded photo in `storage/app/public` is reachable at `public/storage`

*(T005–T007 originally covered a separate `listing_photos` table/model/relationship for up to 3
photos per listing. Manual testing during Phase 6 showed that design added a lot of moving parts —
a second model, reorder/position UI, remove arrays — for a capability that didn't need it, and it
was the source of several real bugs. Replaced with a single nullable `photo_path` column on
`listings` (T001) — see research.md's "single photo" decision. T005–T007 no longer exist.)*

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Validation, authorization, and routing every user story depends on

**⚠️ CRITICAL**: Must be complete before any user story work begins

- [X] T009 Create `app/Http/Requests/ListingRequest.php` — validates `address_line`, `city`, `state_province`, `postal_code`, `country` (`CA`/`US`), `listing_type` (`sale`/`rent`), `property_type` (`house`/`apartment`/`land`/`commercial`) as required; `price` required and greater than zero; `area_sqm`, `description` optional; `photo` (nullable image, max file size) and `remove_photo` (nullable boolean) (FR-001, FR-002, FR-003, FR-011, FR-015)
- [X] T010 [P] Create `app/Policies/ListingPolicy.php` — `update(User $user, Listing $listing): bool` returns `$listing->user_id === $user->id` (FR-006, FR-008)
- [X] T011 [P] Register `/listings` routes in `routes/web.php` — `index`, `create`, `store`, `edit`, `update` (no `destroy`), under the same `auth`, `verified`, `profile.complete` middleware group as `/dashboard`

**Checkpoint**: Schema, validation, and authorization scaffolding ready — user stories can now be built.

---

## Phase 3: User Story 1 - Add a new property listing (Priority: P1) 🎯 MVP

**Goal**: An agent submits a new listing with all its details and it appears afterward in their list, with status `available`.

**Independent Test**: Submit a new listing with all required fields and confirm it appears in `/listings` with status `available`; confirm a missing field or a non-positive price is rejected.

### Tests for User Story 1 ⚠️

- [X] T012 [P] [US1] Write feature tests in `tests/Feature/ListingTest.php` — creating a listing with all required fields (including a `country`) succeeds, appears in `/listings`, defaults to status `available`, and gets its `currency` from the *creating agent's own profile country* (not the listing's own `country`) regardless of request input; a missing required field is rejected; a price of zero or negative is rejected (FR-001 through FR-005, FR-010)

### Implementation for User Story 1

- [X] T013 [US1] Implement `ListingController::index()` — queries only `auth()->user()->listings()->latest()`, ordering newest-created first, returns `resources/views/listings/index.blade.php` (FR-005, FR-006, FR-016)
- [X] T014 [US1] Implement `ListingController::create()` — returns `resources/views/listings/create.blade.php`
- [X] T015 [US1] Implement `ListingController::store(ListingRequest $request)` — creates the `Listing` for `auth()->user()` with the validated `country` from the request, forcing `status = 'available'`, `source = 'manual'`, and `currency` from *`auth()->user()->country`* (`CA` → `CAD`, `US` → `USD`) — not from the listing's own `country` — server-side regardless of any request input, redirects to `/listings` (FR-004, FR-010)
- [X] T016 [P] [US1] Build `resources/views/listings/create.blade.php` — fields for address (address line, city, state/province, postal code, country — all fillable via the same postal/zip code autocomplete JS as the profile form, reusing `/api/address-suggestions`), price, listing type, property type, size, description — the address block is a shared `listings/partials/address-fields.blade.php` partial, also used by `edit.blade.php`
- [X] T017 [P] [US1] Build `resources/views/listings/index.blade.php` — lists the agent's own listings, newest first, with address, price, currency, type, status, and the date it was added

**Checkpoint**: `php artisan test --filter=Listing` passes for creation; User Story 1 is independently testable and demonstrable as the MVP.

---

## Phase 4: User Story 2 - Update a listing's status (Priority: P2)

**Goal**: An agent changes a listing's status among `available`, `pending`, and `closed` as a deal progresses.

**Independent Test**: Create a listing, change its status, and confirm the new status is reflected when viewed again.

### Tests for User Story 2 ⚠️

- [X] T018 [P] [US2] Write a feature test confirming a listing's status can move from `available` to `pending` to `closed`, and that a closed listing remains visible in `/listings` rather than being removed (FR-007, FR-009)

### Implementation for User Story 2

- [X] T019 [US2] Implement `ListingController::edit(Listing $listing)` — `$this->authorize('update', $listing)`, returns `resources/views/listings/edit.blade.php` pre-filled with current values including status
- [X] T020 [US2] Implement `ListingController::update(ListingRequest $request, Listing $listing)` — `$this->authorize('update', $listing)`, updates all fields including `status`, redirects to `/listings` (FR-006, FR-007) — required adding the `AuthorizesRequests` trait to `ListingController` (the Laravel 11+ base `Controller` class ships empty, without it `$this->authorize()` doesn't exist)
- [X] T021 [P] [US2] Build `resources/views/listings/edit.blade.php` — same fields as `create.blade.php` (including address autocomplete) plus a status select (`available`/`pending`/`closed`); this view also serves as the listing's detail page

**Checkpoint**: `php artisan test --filter=Listing` passes for status changes; User Stories 1 and 2 both work independently.

---

## Phase 5: User Story 3 - Manage only your own listings (Priority: P1)

**Goal**: An agent only ever sees and can edit the listings they personally created — never another agent's.

**Independent Test**: Two agents each create a listing; confirm each only sees their own in `/listings`, and that one agent gets a 403 when trying to view or edit the other's listing directly.

### Tests for User Story 3 ⚠️

- [X] T022 [P] [US3] Write a feature test with two agents: Agent A creates a listing; confirm Agent B's `/listings` doesn't include it, and that Agent B gets a 403 on both `GET /listings/{id}/edit` and `PATCH /listings/{id}` for Agent A's listing (FR-006, FR-008)

### Implementation for User Story 3

- [X] T023 [US3] Confirm the ownership boundary — `ListingController::index()` (T013) already scopes to `auth()->user()->listings()` and `ListingPolicy::update()` (T010) already denies cross-agent access on `edit`/`update` (T019, T020); confirmed by inspection and by T022's test, no additional code needed

**Checkpoint**: `php artisan test --filter=Listing` (US1–US3) passes end-to-end.

---

## Phase 6: User Story 4 - Attach a photo to a listing (Priority: P2)

**Goal**: An agent attaches a single photo to a listing, and can replace or remove it afterward.

**Independent Test**: Upload a photo to a listing, confirm it displays; replace it with a different one, then remove it, and confirm each change is reflected.

### Tests for User Story 4 ⚠️

- [X] T024 [P] [US4] Write feature tests in `tests/Feature/ListingTest.php` — uploading a photo on create or update succeeds and it displays; uploading a new photo replaces the old file; submitting `remove_photo` deletes the file and clears `photo_path` (FR-011, FR-012, FR-013)

### Implementation for User Story 4

- [X] T025 [US4] Extend `ListingController::store()` (T015) — if a `photo` file is present, store it via `Storage::disk('public')` and save its path to `photo_path`
- [X] T026 [US4] Extend `ListingController::update()` (T020) — if `remove_photo` is set, delete the existing file and clear `photo_path`; if a new `photo` file is present, delete any existing file first, then store the new one and update `photo_path`
- [X] T027 [P] [US4] Update `resources/views/listings/create.blade.php` (T016) — add a single-file photo input
- [X] T028 [P] [US4] Update `resources/views/listings/edit.blade.php` (T021) — show the current photo (if any) with a "×" remove button, plus a file input to upload/replace it
- [X] T029 [P] [US4] Update `resources/views/listings/index.blade.php` (T017) — show a listing's photo as a thumbnail, if it has one

**Checkpoint**: `php artisan test --filter=Listing` (all four stories) passes end-to-end.

---

## Phase 7: User Story 5 - Filter the list of listings (Priority: P3)

**Goal**: An agent optionally narrows `/listings` by status, listing type, property type, and/or a price range, in any combination; no filter means the full list, as today.

**Independent Test**: Create listings with varying statuses/types/prices, apply one or more filters, confirm only matching listings show, then clear the filters and confirm the full list returns.

### Tests for User Story 5 ⚠️

- [X] T033 [P] [US5] Write feature tests in `tests/Feature/ListingTest.php` — filtering by `status` alone returns only matching listings; combining two filters (e.g., `listing_type` and `max_price`) returns only listings matching all of them; no filter returns the full list (FR-017, FR-018)

### Implementation for User Story 5

- [X] T034 [US5] Extend `ListingController::index()` (T013) — read optional `status`, `listing_type`, `property_type`, `min_price`, `max_price` query parameters and apply each as a `where()` clause only when present — done as part of T013's original implementation
- [X] T035 [P] [US5] Update `resources/views/listings/index.blade.php` (T017) — add a `GET`-submitted filter form (dropdowns for `status`/`listing_type`/`property_type`, number inputs for min/max price)

**Checkpoint**: `php artisan test --filter=Listing` (all five stories) passes end-to-end.

---

## Phase 7b: Permanently delete a listing (added after User Story 2 shipped, FR-019)

**Goal**: An agent can permanently delete one of their own listings, distinct from marking it "closed" (FR-009).

**Independent Test**: Create a listing, delete it (confirming the prompt), and confirm it's gone from `/listings` and the database.

- [X] T039 [P] Write feature tests in `tests/Feature/ListingTest.php` — an agent can delete their own listing (row and photo file both removed); a different agent gets a 403 and the listing is untouched (FR-008, FR-019)
- [X] T040 Add `delete(User $user, Listing $listing): bool` to `app/Policies/ListingPolicy.php`, same check as `update`
- [X] T041 Register `DELETE /listings/{listing}` in `routes/web.php`, same middleware group as the other listing routes; implement `ListingController::destroy()` — authorize, delete the photo file if present, delete the row, redirect to `/listings`
- [X] T042 [P] Add a "Delete this listing" button to `resources/views/listings/edit.blade.php` — its own form (`DELETE`), with a `confirm()` prompt before submitting

**Checkpoint**: `php artisan test --filter=Listing` passes (69/69 whole project suite).

---

## Phase 8: Polish & Cross-Cutting Concerns

- [X] T036 [P] Run the full suite (`php artisan test --filter=Listing`, then the whole project suite) and fix any regressions — 13/13 Listing tests, 67/67 whole project suite
- [X] T037 Walk through `quickstart.md`'s manual validation steps end-to-end in the browser, using two separate agent accounts — confirmed: validation errors, status transitions (including closed listings staying visible), the two-agent ownership boundary (403 on cross-agent access), and combining/clearing filters all work as expected
- [X] T038 [P] Re-read all touched files against the constitution's Simplicity and Explainable Code principles; simplify anything that isn't easy to explain — reviewed `ListingController`, `ListingRequest`, `ListingPolicy`, `Listing`, `ListingPhoto`; added one clarifying comment to `ListingRequest` (the photo-slot math); also found and fixed two real gaps during manual testing: (1) `resources/views/layouts/navigation.blade.php` had no link to `/listings` at all, so the feature was unreachable from the UI; (2) `listings/edit.blade.php` crashed on any listing with photos — `$loop->parent->index` inside a `@for` nested in the photos `@foreach` was wrong (`@for` doesn't introduce its own `$loop`, so `$loop` already referred to the `@foreach` — no `->parent` needed); fixed and added a regression test (`test_edit_page_renders_correctly_when_the_listing_has_photos`) since no existing test had loaded that page with photos attached. Also ran `npm run build` — the compiled Tailwind CSS predated this feature (and Feature 3), so none of the new views' classes were in it. During manual testing, found that browsers (Safari's back/forward cache especially) were serving stale copies of authenticated pages instead of re-fetching after a change — fixed with a new `PreventBrowserCaching` middleware (aliased `no-cache`) applied to `/dashboard`, `/profile`, and `/listings` routes, setting `Cache-Control: no-store, no-cache, must-revalidate`; covered by `tests/Feature/PreventBrowserCachingTest.php`. This is a cross-cutting fix, not scoped to Listings alone, but was only surfaced by testing this feature. Finally, after further manual testing showed the up-to-3-photos design (separate `ListingPhoto` table, reorder/remove UI) kept causing bugs and confusion, simplified to a single `photo_path` column per listing (T001, T009, T025–T029 revised accordingly; `ListingPhoto` model/factory/migration removed) — see research.md. Also added a live photo preview (with its own "×" to clear the selection before saving) to `listings/partials/photo-input.blade.php`, and changed `listings/index.blade.php` (T017/T029) from a table to a responsive card grid, per direct user feedback during manual testing.

---

## Dependencies & Execution Order

- **Setup (Phase 1)**: No dependencies — start immediately.
- **Foundational (Phase 2)**: Depends on Setup — blocks every user story.
- **User Story 1 (Phase 3)**: Depends only on Foundational. Independently testable and deployable as the MVP.
- **User Story 2 (Phase 4)**: Depends only on Foundational, but naturally follows US1 since it edits a listing US1 creates.
- **User Story 3 (Phase 5)**: Depends on Foundational, and on US1's `index` (T013) and US2's `edit`/`update` (T019, T020) existing to have anything to verify.
- **User Story 4 (Phase 6)**: Depends on US1's `store` (T015) and US2's `update` (T020), which it extends to handle photos.
- **User Story 5 (Phase 7)**: Depends on US1's `index` (T013), which it extends to handle filters.
- **Polish (Phase 8)**: Depends on all five user stories being complete.

### Parallel Opportunities

- T002, T003, T004 (Setup) touch different files and can run in parallel once T001's migration exists.
- T009, T010, T011 (Foundational) touch different files and can run in parallel.
- T016, T017 (US1 views) can run in parallel with T013–T015 since they touch different files.
- T021 (US2 view) can run in parallel with T019–T020.
- T027, T028, T029 (US4 views) can run in parallel with each other, but not with T025/T026 (same controller file).
- T012, T018, T022, T024, T033 (test-writing tasks) can each be written in parallel with other work in their own phase.

---

## Implementation Strategy

### MVP First

Phase 3 (User Story 1) alone is independently testable and demonstrable — an agent can add and see
their own listings end-to-end. Status changes (Phase 4), the explicit ownership test (Phase 5), a
photo (Phase 6), and filtering (Phase 7) add value but aren't required for the MVP to be usable.

### Incremental Delivery

1. Setup + Foundational → schema, validation, and authorization ready
2. User Story 1 → agents can add and view their own listings (MVP)
3. User Story 2 → agents can update a listing's status as deals progress
4. User Story 3 → confirmed the ownership boundary holds under a two-agent test
5. User Story 4 → agents can attach, replace, and remove a photo
6. User Story 5 → agents can filter a long list down to what they're looking for
7. Polish → full suite green, manual quickstart walkthrough in the browser
