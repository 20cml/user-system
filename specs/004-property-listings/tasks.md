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

- [ ] T001 Create the `listings` migration in `database/migrations/` — all fields from data-model.md (`user_id`, `source`, `listing_type`, `property_type`, `status` default `available`, `address_line`, `city`, `state_province`, `postal_code`, `country`, `price`, `currency`, `area_sqm`, `description`)
- [ ] T002 [P] Create `app/Models/Listing.php` — fillable fields, `price`/`area_sqm` cast to `decimal`, `belongsTo(User::class)`
- [ ] T003 [P] Add a `listings()` `hasMany(Listing::class)` relationship to `app/Models/User.php`
- [ ] T004 [P] Create `database/factories/ListingFactory.php` with realistic fake data for every field
- [ ] T005 Create the `listing_photos` migration in `database/migrations/` — `listing_id` (foreign key), `path`, `sort_order`
- [ ] T006 [P] Create `app/Models/ListingPhoto.php` — fillable fields, `belongsTo(Listing::class)`
- [ ] T007 [P] Add a `photos()` `hasMany(ListingPhoto::class)` relationship, ordered by `sort_order`, to `app/Models/Listing.php`
- [ ] T008 Run `php artisan storage:link` (if not already linked) so uploaded photos in `storage/app/public` are reachable at `public/storage`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Validation, authorization, and routing every user story depends on

**⚠️ CRITICAL**: Must be complete before any user story work begins

- [ ] T009 Create `app/Http/Requests/ListingRequest.php` — validates `address_line`, `city`, `state_province`, `postal_code`, `country` (`CA`/`US`), `listing_type` (`sale`/`rent`), `property_type` (`house`/`apartment`/`land`/`commercial`) as required; `price` required and greater than zero; `area_sqm`, `description` optional; `photos.*` (image, reasonable max file size) capped so existing + new photos never exceed 3; `remove_photos.*` must reference an existing photo on this listing; `photo_order` optional array (FR-001, FR-002, FR-003, FR-011, FR-012, FR-015)
- [ ] T010 [P] Create `app/Policies/ListingPolicy.php` — `update(User $user, Listing $listing): bool` returns `$listing->user_id === $user->id` (FR-006, FR-008)
- [ ] T011 [P] Register `/listings` routes in `routes/web.php` — `index`, `create`, `store`, `edit`, `update` (no `destroy`), under the same `auth`, `verified`, `profile.complete` middleware group as `/dashboard`

**Checkpoint**: Schema, validation, and authorization scaffolding ready — user stories can now be built.

---

## Phase 3: User Story 1 - Add a new property listing (Priority: P1) 🎯 MVP

**Goal**: An agent submits a new listing with all its details and it appears afterward in their list, with status `available`.

**Independent Test**: Submit a new listing with all required fields and confirm it appears in `/listings` with status `available`; confirm a missing field or a non-positive price is rejected.

### Tests for User Story 1 ⚠️

- [ ] T012 [P] [US1] Write feature tests in `tests/Feature/ListingTest.php` — creating a listing with all required fields (including a `country`) succeeds, appears in `/listings`, defaults to status `available`, and gets its `currency` from the *creating agent's own profile country* (not the listing's own `country`) regardless of request input; a missing required field is rejected; a price of zero or negative is rejected (FR-001 through FR-005, FR-010)

### Implementation for User Story 1

- [ ] T013 [US1] Implement `ListingController::index()` — queries only `auth()->user()->listings()->latest()`, ordering newest-created first, returns `resources/views/listings/index.blade.php` (FR-005, FR-006, FR-016)
- [ ] T014 [US1] Implement `ListingController::create()` — returns `resources/views/listings/create.blade.php`
- [ ] T015 [US1] Implement `ListingController::store(ListingRequest $request)` — creates the `Listing` for `auth()->user()` with the validated `country` from the request, forcing `status = 'available'`, `source = 'manual'`, and `currency` from *`auth()->user()->country`* (`CA` → `CAD`, `US` → `USD`) — not from the listing's own `country` — server-side regardless of any request input, redirects to `/listings` (FR-004, FR-010)
- [ ] T016 [P] [US1] Build `resources/views/listings/create.blade.php` — fields for address (address line, city, state/province, postal code, country — all fillable via the same postal/zip code autocomplete JS as the profile form, reusing `/api/address-suggestions`), price, listing type, property type, size, description
- [ ] T017 [P] [US1] Build `resources/views/listings/index.blade.php` — lists the agent's own listings, newest first, with address, price, currency, type, status, and the date it was added

**Checkpoint**: `php artisan test --filter=Listing` passes for creation; User Story 1 is independently testable and demonstrable as the MVP.

---

## Phase 4: User Story 2 - Update a listing's status (Priority: P2)

**Goal**: An agent changes a listing's status among `available`, `pending`, and `closed` as a deal progresses.

**Independent Test**: Create a listing, change its status, and confirm the new status is reflected when viewed again.

### Tests for User Story 2 ⚠️

- [ ] T018 [P] [US2] Write a feature test confirming a listing's status can move from `available` to `pending` to `closed`, and that a closed listing remains visible in `/listings` rather than being removed (FR-007, FR-009)

### Implementation for User Story 2

- [ ] T019 [US2] Implement `ListingController::edit(Listing $listing)` — `$this->authorize('update', $listing)`, returns `resources/views/listings/edit.blade.php` pre-filled with current values including status
- [ ] T020 [US2] Implement `ListingController::update(ListingRequest $request, Listing $listing)` — `$this->authorize('update', $listing)`, updates all fields including `status`, redirects to `/listings` (FR-006, FR-007)
- [ ] T021 [P] [US2] Build `resources/views/listings/edit.blade.php` — same fields as `create.blade.php` (including address autocomplete) plus a status select (`available`/`pending`/`closed`); this view also serves as the listing's detail page

**Checkpoint**: `php artisan test --filter=Listing` passes for status changes; User Stories 1 and 2 both work independently.

---

## Phase 5: User Story 3 - Manage only your own listings (Priority: P1)

**Goal**: An agent only ever sees and can edit the listings they personally created — never another agent's.

**Independent Test**: Two agents each create a listing; confirm each only sees their own in `/listings`, and that one agent gets a 403 when trying to view or edit the other's listing directly.

### Tests for User Story 3 ⚠️

- [ ] T022 [P] [US3] Write a feature test with two agents: Agent A creates a listing; confirm Agent B's `/listings` doesn't include it, and that Agent B gets a 403 on both `GET /listings/{id}/edit` and `PATCH /listings/{id}` for Agent A's listing (FR-006, FR-008)

### Implementation for User Story 3

- [ ] T023 [US3] Confirm the ownership boundary — `ListingController::index()` (T013) already scopes to `auth()->user()->listings()` and `ListingPolicy::update()` (T010) already denies cross-agent access on `edit`/`update` (T019, T020); confirmed by inspection and by T022's test, no additional code needed

**Checkpoint**: `php artisan test --filter=Listing` (US1–US3) passes end-to-end.

---

## Phase 6: User Story 4 - Attach photos to a listing (Priority: P2)

**Goal**: An agent attaches up to 3 photos to a listing, and can remove or reorder them afterward.

**Independent Test**: Upload photos to a listing, confirm they display in order; remove one and reorder the rest, and confirm both changes are reflected.

### Tests for User Story 4 ⚠️

- [ ] T024 [P] [US4] Write feature tests in `tests/Feature/ListingTest.php` — uploading 1 to 3 photos on create or update succeeds and they display in order; attempting to exceed 3 total is rejected; submitting `remove_photos` deletes the photo (file and row); submitting `photo_order` updates `sort_order` accordingly (FR-011 through FR-014)

### Implementation for User Story 4

- [ ] T025 [US4] Extend `ListingController::store()` (T015) — store each uploaded `photos[]` file via `Storage::disk('public')`, creating a `ListingPhoto` per file with sequential `sort_order`
- [ ] T026 [US4] Extend `ListingController::update()` (T020) — append newly uploaded `photos[]` (respecting the 3-photo cap), delete any `remove_photos[]` (file and row), and reassign `sort_order` from `photo_order` when present
- [ ] T027 [P] [US4] Update `resources/views/listings/create.blade.php` (T016) — add a multi-file photo input (up to 3)
- [ ] T028 [P] [US4] Update `resources/views/listings/edit.blade.php` (T021) — show existing photos, each with a "remove" checkbox and a position select (1st/2nd/3rd), plus an input to add more photos up to the remaining slots
- [ ] T029 [P] [US4] Update `resources/views/listings/index.blade.php` (T017) — show each listing's first photo (by `sort_order`) as a thumbnail, if it has one

**Checkpoint**: `php artisan test --filter=Listing` (all four stories) passes end-to-end.

---

## Phase 7: User Story 5 - Filter the list of listings (Priority: P3)

**Goal**: An agent optionally narrows `/listings` by status, listing type, property type, and/or a price range, in any combination; no filter means the full list, as today.

**Independent Test**: Create listings with varying statuses/types/prices, apply one or more filters, confirm only matching listings show, then clear the filters and confirm the full list returns.

### Tests for User Story 5 ⚠️

- [ ] T033 [P] [US5] Write feature tests in `tests/Feature/ListingTest.php` — filtering by `status` alone returns only matching listings; combining two filters (e.g., `listing_type` and `max_price`) returns only listings matching all of them; no filter returns the full list (FR-017, FR-018)

### Implementation for User Story 5

- [ ] T034 [US5] Extend `ListingController::index()` (T013) — read optional `status`, `listing_type`, `property_type`, `min_price`, `max_price` query parameters and apply each as a `where()` clause only when present
- [ ] T035 [P] [US5] Update `resources/views/listings/index.blade.php` (T017) — add a `GET`-submitted filter form (dropdowns for `status`/`listing_type`/`property_type`, number inputs for min/max price)

**Checkpoint**: `php artisan test --filter=Listing` (all five stories) passes end-to-end.

---

## Phase 8: Polish & Cross-Cutting Concerns

- [ ] T036 [P] Run the full suite (`php artisan test --filter=Listing`, then the whole project suite) and fix any regressions
- [ ] T037 Walk through `quickstart.md`'s manual validation steps end-to-end in the browser, using two separate agent accounts
- [ ] T038 [P] Re-read all touched files against the constitution's Simplicity and Explainable Code principles; simplify anything that isn't easy to explain

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

- T002, T003, T004 (Setup) touch different files and can run in parallel once T001's migration exists; T006, T007 similarly once T005's migration exists.
- T009, T010, T011 (Foundational) touch different files and can run in parallel.
- T016, T017 (US1 views) can run in parallel with T013–T015 since they touch different files.
- T021 (US2 view) can run in parallel with T019–T020.
- T027, T028, T029 (US4 views) can run in parallel with each other, but not with T025/T026 (same controller file).
- T012, T018, T022, T024, T033 (test-writing tasks) can each be written in parallel with other work in their own phase.

---

## Implementation Strategy

### MVP First

Phase 3 (User Story 1) alone is independently testable and demonstrable — an agent can add and see
their own listings end-to-end. Status changes (Phase 4), the explicit ownership test (Phase 5),
photos (Phase 6), and filtering (Phase 7) add value but aren't required for the MVP to be usable.

### Incremental Delivery

1. Setup + Foundational → schema, validation, and authorization ready
2. User Story 1 → agents can add and view their own listings (MVP)
3. User Story 2 → agents can update a listing's status as deals progress
4. User Story 3 → confirmed the ownership boundary holds under a two-agent test
5. User Story 4 → agents can attach, remove, and reorder photos
6. User Story 5 → agents can filter a long list down to what they're looking for
7. Polish → full suite green, manual quickstart walkthrough in the browser
