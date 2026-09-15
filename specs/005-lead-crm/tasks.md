---

description: "Task list for Lead CRM"
---

# Tasks: Lead CRM

**Input**: Design documents from `/specs/005-lead-crm/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/, quickstart.md (all present)

**Tests**: Included — the project constitution (Principle IV) requires automated test coverage for this feature's core flows.

**Organization**: Tasks are grouped by user story so each can be implemented and tested independently.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (US1, US2, US3, US4, US5)

## Phase 1: Setup

**Purpose**: Base data layer every story depends on

- [X] T001 Create the `leads` migration in `database/migrations/` — `user_id` (foreign key), `name`, `phone` (nullable), `email` (nullable), `status` default `new`
- [X] T002 [P] Create `app/Models/Lead.php` — fillable fields, `belongsTo(User::class)` — also included the `listings()`/`notes()` relationships from T006/T010 while the file was open
- [X] T003 [P] Add a `leads()` `hasMany(Lead::class)` relationship to `app/Models/User.php`
- [X] T004 [P] Create `database/factories/LeadFactory.php` with realistic fake data for every field

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Relationships, validation, and routing every user story depends on

**⚠️ CRITICAL**: Must be complete before any user story work begins

- [X] T005 Create the `lead_listing` pivot migration in `database/migrations/` — `lead_id` and `listing_id` (both foreign keys, `cascadeOnDelete()`), no dedicated model (see research.md)
- [X] T006 [P] Add a `listings()` `belongsToMany(Listing::class, 'lead_listing')` relationship to `app/Models/Lead.php` — done alongside T002
- [X] T007 [P] Add a `leads()` `belongsToMany(Lead::class, 'lead_listing')` relationship to `app/Models/Listing.php`
- [X] T008 Create the `lead_notes` migration in `database/migrations/` — `lead_id` (foreign key, `cascadeOnDelete()`), `body` (text)
- [X] T009 [P] Create `app/Models/LeadNote.php` — fillable fields, `belongsTo(Lead::class)`
- [X] T010 [P] Add a `notes()` `hasMany(LeadNote::class)->latest()` relationship to `app/Models/Lead.php` — done alongside T002
- [X] T011 [P] Create `database/factories/LeadNoteFactory.php`
- [X] T012 Create `app/Http/Requests/LeadRequest.php` — validates `name` as required; `phone`, `email` optional; `status` (`sometimes`, one of the six values); `listing_ids` (array, each ID must exist in `auth()->user()->listings`) (FR-002, FR-006, FR-007)
- [X] T013 [P] Create `app/Policies/LeadPolicy.php` — `update(User $user, Lead $lead): bool` returns `$lead->user_id === $user->id` (FR-009) — also added `delete()` here directly (originally planned for T037/Phase 8), same check
- [X] T014 [P] Register the core `/leads` routes in `routes/web.php` — `index`, `create`, `store`, `edit`, `update`, under the same `auth`, `verified`, `profile.complete`, `no-cache` middleware group as `/listings` — also registered `destroy` (T038) and `notes.store` (T032) here since it was the same file edit

**Checkpoint**: Schema, validation, and authorization scaffolding ready — user stories can now be built.

---

## Phase 3: User Story 1 - Add a new lead (Priority: P1) 🎯 MVP

**Goal**: An agent submits a new lead with a name (and optionally phone/email) and it appears afterward in their list, with status `new`.

**Independent Test**: Submit a new lead with a name and confirm it appears in `/leads` with status `new`; confirm a missing name is rejected.

### Tests for User Story 1 ⚠️

- [X] T015 [P] [US1] Write feature tests in `tests/Feature/LeadTest.php` — creating a lead with a name succeeds, appears in `/leads`, defaults to status `new`; a missing name is rejected (FR-001, FR-002, FR-003, FR-004)

### Implementation for User Story 1

- [X] T016 [US1] Implement `LeadController::index()` — queries only `auth()->user()->leads()->latest()`, returns `resources/views/leads/index.blade.php`
- [X] T017 [US1] Implement `LeadController::create()` — returns `resources/views/leads/create.blade.php`
- [X] T018 [US1] Implement `LeadController::store(LeadRequest $request)` — creates the `Lead` for `auth()->user()`, forcing `status = 'new'` server-side regardless of any request input, redirects to `/leads`
- [X] T019 [P] [US1] Build `resources/views/leads/create.blade.php` — fields for name, phone, email
- [X] T020 [P] [US1] Build `resources/views/leads/index.blade.php` — lists the agent's own leads, newest first, with name, status, and phone/email

**Checkpoint**: `php artisan test --filter=Lead` passes for creation; User Story 1 is independently testable and demonstrable as the MVP.

---

## Phase 4: User Story 2 - Mark which listings a lead is interested in (Priority: P1)

**Goal**: An agent links a lead to one or more of their own listings, and that link is visible afterward.

**Independent Test**: Link a lead to a listing and confirm it's checked when viewing the lead again; confirm a listing ID that isn't the agent's own is rejected.

### Tests for User Story 2 ⚠️

- [X] T021 [P] [US2] Write feature tests in `tests/Feature/LeadTest.php` — creating or editing a lead with one or more `listing_ids` links them (visible via `$lead->listings`); submitting a listing ID belonging to a different agent is rejected (FR-007)

### Implementation for User Story 2

- [X] T022 [US2] Extend `LeadController::store()` (T018) — after creating the lead, `$lead->listings()->sync($request->validated('listing_ids', []))`
- [X] T023 [US2] Implement `LeadController::edit(Lead $lead)` — `$this->authorize('update', $lead)`, returns `resources/views/leads/edit.blade.php` with the lead, the agent's listings, and which are currently linked
- [X] T024 [US2] Implement `LeadController::update(LeadRequest $request, Lead $lead)` — `$this->authorize('update', $lead)`, fills the lead's fields, syncs `listing_ids`, redirects to `/leads`
- [X] T025 [P] [US2] Update `resources/views/leads/create.blade.php` (T019) — add a checklist of the agent's own listings — done alongside T019
- [X] T026 [P] [US2] Build `resources/views/leads/edit.blade.php` — same fields as `create.blade.php` plus the listing checklist; this view also serves as the lead's detail page — built with the status select (T029) and notes section (T033) included at the same time

**Checkpoint**: `php artisan test --filter=Lead` passes for linking; User Stories 1 and 2 both work independently.

---

## Phase 5: User Story 3 - Update a lead's status through the sales process (Priority: P2)

**Goal**: An agent changes a lead's status among the six funnel values as things progress.

**Independent Test**: Create a lead, change its status, and confirm the new status is reflected when viewed again; confirm a `closed`/`lost` lead still appears in the list.

### Tests for User Story 3 ⚠️

- [X] T027 [P] [US3] Write a feature test confirming a lead's status can move between values (e.g., `new` → `qualified` → `lost`), and that a `closed`/`lost` lead remains visible in `/leads` rather than being removed (FR-006, FR-010)

### Implementation for User Story 3

- [X] T028 [US3] Confirm `LeadController::update()` (T024) already persists `status` via the validated/filled fields from `LeadRequest` (T012) — no additional controller code needed
- [X] T029 [P] [US3] Update `resources/views/leads/edit.blade.php` (T026) — add a status select (`new`/`qualified`/`visited`/`proposal`/`closed`/`lost`) — done alongside T026

**Checkpoint**: `php artisan test --filter=Lead` passes for status changes; User Stories 1–3 all work independently.

---

## Phase 6: User Story 4 - Log notes about interactions with a lead (Priority: P2)

**Goal**: An agent adds timestamped notes to a lead, shown most recent first.

**Independent Test**: Add a note to a lead and confirm it appears, timestamped; add a second note and confirm both show, most recent first.

### Tests for User Story 4 ⚠️

- [X] T030 [P] [US4] Write feature tests in `tests/Feature/LeadTest.php` — adding a note to a lead succeeds and displays with its date; a second note appears above the first (most recent first); an empty note body is rejected (FR-008) — this test caught a real bug (see T010)
- [X] T010-fix `Lead::notes()` ordered by `latest()` (i.e. `created_at`) alone isn't reliable when two notes are added within the same second — identical timestamps left ordering undefined. Fixed by ordering `latest('id')` instead, since `id` always reflects true insertion order.

### Implementation for User Story 4

- [X] T031 [US4] Implement `LeadController::storeNote(Request $request, Lead $lead)` — `$this->authorize('update', $lead)`, validates `body` required, creates a `LeadNote`, redirects back to `/leads/{lead}/edit`
- [X] T032 Register `POST /leads/{lead}/notes` in `routes/web.php`, same middleware group as the other lead routes — done alongside T014
- [X] T033 [P] [US4] Update `resources/views/leads/edit.blade.php` (T029) — show the lead's notes (via `$lead->notes`, already latest-first) with their date, plus a small form to add a new one — done alongside T026

**Checkpoint**: `php artisan test --filter=Lead` passes for notes; User Stories 1–4 all work independently.

---

## Phase 7: User Story 5 - Manage only your own leads (Priority: P1)

**Goal**: An agent only ever sees and can edit the leads they personally created — never another agent's.

**Independent Test**: Two agents each create a lead; confirm each only sees their own in `/leads`, and that one agent gets a 403 when trying to view, edit, or add a note to the other's lead directly.

### Tests for User Story 5 ⚠️

- [X] T034 [P] [US5] Write a feature test with two agents: Agent A creates a lead; confirm Agent B's `/leads` doesn't include it, and that Agent B gets a 403 on `GET /leads/{id}/edit`, `PATCH /leads/{id}`, and `POST /leads/{id}/notes` for Agent A's lead (FR-009) — also included the `DELETE` case here (T036's boundary half)

### Implementation for User Story 5

- [X] T035 [US5] Confirm the ownership boundary — `LeadController::index()` (T016) already scopes to `auth()->user()->leads()` and `LeadPolicy::update()` (T013) already denies cross-agent access on `edit`/`update`/`storeNote` (T023, T024, T031); confirmed by inspection and by T034's test, no additional code needed

**Checkpoint**: `php artisan test --filter=Lead` (all five stories) passes end-to-end.

---

## Phase 8: Permanently delete a lead (FR-011)

**Goal**: An agent can permanently delete one of their own leads, distinct from marking it `closed`/`lost` (FR-010).

**Independent Test**: Create a lead, delete it (confirming the prompt), and confirm it's gone from `/leads` and the database (notes cascade-deleted too).

- [X] T036 [P] Write feature tests in `tests/Feature/LeadTest.php` — an agent can delete their own lead (row and notes both removed via cascade); a different agent gets a 403 and the lead is untouched (FR-009, FR-011)
- [X] T037 Add `delete(User $user, Lead $lead): bool` to `app/Policies/LeadPolicy.php` (T013), same check as `update` — done alongside T013
- [X] T038 Register `DELETE /leads/{lead}` in `routes/web.php`; implement `LeadController::destroy(Lead $lead)` — authorize, delete the lead (notes cascade via the database foreign key), redirect to `/leads` — done alongside T014/controller
- [X] T039 [P] Add a "Delete this lead" button to `resources/views/leads/edit.blade.php` — its own form (`DELETE`), with a `confirm()` prompt before submitting — done alongside T026

**Checkpoint**: `php artisan test --filter=Lead` passes end-to-end, including deletion.

---

## Phase 9: Polish & Cross-Cutting Concerns

- [X] T040 [P] Run the full suite (`php artisan test --filter=Lead`, then the whole project suite) and fix any regressions — 10/10 Lead tests, 79/79 whole project suite
- [X] T041 Walk through `quickstart.md`'s manual validation steps end-to-end in the browser, using two separate agent accounts — verified, including the ownership boundary between agents
- [X] T042 [P] Re-read all touched files against the constitution's Simplicity and Explainable Code principles; simplify anything that isn't easy to explain — reviewed `LeadController`, `LeadRequest`, `LeadPolicy`, `Lead`, `LeadNote`; also added `resources/views/layouts/navigation.blade.php` link to `/leads` (missed this for Listings originally, remembered to do it upfront this time)

---

## Phase 10: Replace the listing checklist with a searchable "tag picker" (added after manual testing)

**Goal**: Linking a lead to listings (and vice versa) uses a search-as-you-type picker instead of a
checkbox list of every one of the agent's records, since that doesn't scale to ~200 listings
(spec SC-004) — and works symmetrically from both the Lead's page and the Listing's page.

- [X] T043 [P] Write feature tests — `tests/Feature/ListingSearchTest.php` (search by address/city/ID, scoped to the agent, empty query returns `[]`) and `tests/Feature/LeadSearchTest.php` (search by name, scoped to the agent, empty query returns `[]`)
- [X] T044 Create `app/Http/Controllers/Api/ListingSearchController.php` — searches `auth()->user()->listings()` by `address_line`/`city` or exact numeric `id`, returns `{id, label}` JSON, capped at 10 results
- [X] T045 Create `app/Http/Controllers/Api/LeadSearchController.php` — searches `auth()->user()->leads()` by `name`, returns `{id, label}` JSON, capped at 10 results
- [X] T046 Register `GET /api/listings-search` and `GET /api/leads-search` in `routes/web.php`, `auth`-only (same group as `/api/address-suggestions`)
- [X] T047 Create `resources/views/partials/tag-picker.blade.php` — a reusable search-input + removable-chip component (vanilla JS, debounced fetch, hidden `{field}_ids[]` inputs), parameterized by field name, search URL, placeholder, and already-selected items
- [X] T048 [P] Replace the listing checkbox list in `resources/views/leads/create.blade.php` and `resources/views/leads/edit.blade.php` with the tag picker (searching `/api/listings-search`)
- [X] T049 [P] Add an "Interested leads" tag picker (searching `/api/leads-search`) to `resources/views/listings/create.blade.php` and `resources/views/listings/edit.blade.php`
- [X] T050 Extend `ListingController::store()`/`update()` to sync `lead_ids`, and add `lead_ids`/`lead_ids.*` validation (each must belong to the agent) to `ListingRequest` — mirrors how `LeadRequest`/`LeadController` already handle `listing_ids`
- [X] T051 [P] Write feature tests in `tests/Feature/ListingTest.php` — linking leads to a listing on create is visible on edit; linking another agent's lead is rejected

**Checkpoint**: `php artisan test` — 86/86 passing, no regressions.

---

## Phase 11: Allow deleting a single note (added after direct user feedback)

**Goal**: An agent can permanently delete one note from a lead, without affecting its other notes or the lead itself (FR-012).

- [X] T052 [P] Write feature tests in `tests/Feature/LeadTest.php` — an agent can delete a note from their own lead; deleting a note through the wrong one of the agent's own leads (mismatched `lead_id`) is rejected (404); a different agent gets a 403 and the note is untouched
- [X] T053 Register `DELETE /leads/{lead}/notes/{note}` in `routes/web.php`; implement `LeadController::destroyNote(Lead $lead, LeadNote $note)` — `$this->authorize('update', $lead)` (reuses `LeadPolicy`, no new policy needed since a note's ownership flows through its lead), `abort_unless($note->lead_id === $lead->id, 404)`, delete, redirect to `/leads/{lead}/edit`
- [X] T054 [P] Add a small "Delete" link per note in `resources/views/leads/edit.blade.php`, its own tiny form (`DELETE`) with a `confirm()` prompt

**Checkpoint**: `php artisan test` — 88/88 passing, no regressions.

---

## Phase 12: Split `name` into `first_name`/`last_name` (added after direct user feedback)

**Goal**: A lead's name is captured as `first_name` (required) and `last_name` (optional), for
consistency with `User` (Feature 1), while every existing display/search feature keeps working
unchanged.

- [X] T055 Edit `database/migrations/..._create_leads_table.php` (not yet committed/shipped) — replace `name` with `first_name` (required) and `last_name` (nullable); rerun via rollback + migrate locally
- [X] T056 Update `app/Models/Lead.php` — `first_name`/`last_name` in `Fillable`; add a computed `name` accessor (`trim("{first_name} {last_name}")`) so existing code reading `$lead->name` needs no changes
- [X] T057 [P] Update `database/factories/LeadFactory.php` to generate `first_name`/`last_name` instead of `name`
- [X] T058 Update `app/Http/Requests/LeadRequest.php` — `first_name` required, `last_name` nullable, replacing the single `name` rule
- [X] T059 Update `app/Http/Controllers/Api/LeadSearchController.php` — search `first_name` OR `last_name` instead of `name` (the computed accessor isn't a real column, so it can't be queried directly)
- [X] T060 [P] Update `resources/views/leads/create.blade.php` and `resources/views/leads/edit.blade.php` — split the single "Name" field into "First name" and "Last name" inputs
- [X] T061 [P] Update `tests/Feature/LeadTest.php`, `tests/Feature/LeadSearchTest.php`, and `tests/Feature/ListingTest.php` to use `first_name`/`last_name` in factory calls and request payloads

**Checkpoint**: `php artisan test` — 88/88 passing, no regressions.

---

## Phase 13: Search leads by name from the index (added after direct user feedback)

**Goal**: With many leads, an agent can search by name from `/leads` and jump straight to a match's edit page, instead of scanning the whole list.

- [X] T062 [P] Add a search input to `resources/views/leads/index.blade.php` — reuses `/api/leads-search` (already built for the tag picker, no backend change needed); each result links to `/leads/{id}/edit`
- [X] T063 [P] Extend `tests/Feature/LeadTest.php`'s existing index test to confirm the search input renders on the page

**Checkpoint**: `php artisan test` — 88/88 passing, no regressions.

---

## Dependencies & Execution Order

- **Setup (Phase 1)**: No dependencies — start immediately.
- **Foundational (Phase 2)**: Depends on Setup — blocks every user story.
- **User Story 1 (Phase 3)**: Depends only on Foundational. Independently testable and deployable as the MVP.
- **User Story 2 (Phase 4)**: Depends only on Foundational, but naturally follows US1 since it extends `store()` and introduces `edit`/`update` for a lead US1 creates.
- **User Story 3 (Phase 5)**: Depends on US2's `update()` (T024), which it reuses as-is.
- **User Story 4 (Phase 6)**: Depends on US2's `edit()` (T023), whose view it extends to show/add notes.
- **User Story 5 (Phase 7)**: Depends on Foundational, and on US1's `index` (T016) and US2's `edit`/`update` (T023, T024) existing to have anything to verify.
- **Delete (Phase 8)**: Depends on Foundational (`LeadPolicy`, routes) — otherwise independent of the five user stories.
- **Polish (Phase 9)**: Depends on everything above being complete.

### Parallel Opportunities

- T002, T003, T004 (Setup) touch different files and can run in parallel once T001's migration exists.
- T006, T007, T009, T010, T011 (Foundational) touch different files and can run in parallel once T005/T008's migrations exist; T012, T013, T014 similarly touch different files.
- T019, T020 (US1 views) can run in parallel with T016–T018 since they touch different files.
- T025 (US2 create view update) can run in parallel with T022–T024.
- T015, T021, T027, T030, T034, T036 (test-writing tasks) can each be written in parallel with other work in their own phase.

---

## Implementation Strategy

### MVP First

Phase 3 (User Story 1) alone is independently testable and demonstrable — an agent can add and see
their own leads end-to-end. Linking to listings (Phase 4), status changes (Phase 5), notes
(Phase 6), the explicit ownership test (Phase 7), and deletion (Phase 8) add value but aren't
required for the MVP to be usable.

### Incremental Delivery

1. Setup + Foundational → schema, validation, and authorization ready
2. User Story 1 → agents can add and view their own leads (MVP)
3. User Story 2 → agents can link a lead to the listings they're interested in
4. User Story 3 → agents can move a lead through the sales funnel
5. User Story 4 → agents can log notes about each lead over time
6. User Story 5 → confirmed the ownership boundary holds under a two-agent test
7. Delete → agents can permanently remove a lead, distinct from closing/losing it
8. Polish → full suite green, manual quickstart walkthrough in the browser
