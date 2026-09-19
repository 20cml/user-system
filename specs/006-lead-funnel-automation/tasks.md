# Tasks: Lead Funnel Automation

**Input**: Design documents from `/specs/006-lead-funnel-automation/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/

**Tests**: Included — constitution Principle IV requires automated coverage for every user-facing
flow a feature introduces.

**Organization**: Tasks are grouped by user story (spec.md) to enable independent implementation
and testing of each.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (US1–US4)

---

## Phase 1: Setup

- [X] T001 Create a new migration adding `type` (nullable string), `financing_preapproval`,
      `financing_income_proof`, `financing_id_document` (booleans, default false) to the `leads`
      table in `database/migrations/2026_xx_xx_xxxxxx_add_funnel_fields_to_leads_table.php` — a new
      migration, not an edit to the already-shipped `create_leads_table` migration

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: The schema, model behavior, and validation every user story depends on.

**⚠️ CRITICAL**: No user story work can begin until this phase is complete.

- [X] T002 Run `php artisan migrate` and confirm the `leads` table has the 4 new columns
- [X] T003 Add `type`, `financing_preapproval`, `financing_income_proof`, `financing_id_document` to
      `$fillable` and to `casts()` (the 3 checklist fields as `boolean`) in `app/Models/Lead.php`
- [X] T004 Implement `Lead::advanceBuyerFunnel()` in `app/Models/Lead.php` per `research.md` —
      no-ops for non-Buyer or Lost leads; otherwise advances New→Contacted (has a note),
      Contacted→Qualified (checklist complete), Qualified→Active Search (has a linked listing), in
      that order, within a single call
- [X] T005 In `app/Http/Requests/LeadRequest.php`: add `type` (`nullable|in:buyer,seller,investor,renter,landlord`)
      and the 3 checklist fields (`boolean`); expand the `status` rule's accepted values to include
      `contacted` and `active_search`

**Checkpoint**: Foundation ready — user story implementation can now begin.

---

## Phase 3: User Story 1 - Classify a lead by type (Priority: P1) 🎯 MVP

**Goal**: An agent can set a lead's type to Buyer, Seller, Investor, Renter, or Landlord.

**Independent Test**: Create/edit leads with each type value and confirm each saves and displays
correctly, independent of any funnel behavior.

### Tests for User Story 1

- [X] T006 [P] [US1] Test creating a lead with each of the 5 `type` values persists correctly, in
      `tests/Feature/LeadTest.php`
- [X] T007 [P] [US1] Test a lead created without a `type` saves as `null` and is treated as
      non-Buyer (no automatic transitions apply to it), in `tests/Feature/LeadTest.php`

### Implementation for User Story 1

- [X] T008 [US1] Add a "Lead Type" `<select>` (Buyer/Seller/Investor/Renter/Landlord, optional) to
      `resources/views/leads/create.blade.php`
- [X] T009 [US1] Add the same "Lead Type" `<select>` to `resources/views/leads/edit.blade.php`

**Checkpoint**: User Story 1 is fully functional and testable independently.

---

## Phase 4: User Story 2 - Buyer lead advances through the funnel automatically (Priority: P2)

**Goal**: A Buyer lead's status moves New → Contacted → Qualified → Active Search on its own, as
the agent adds a note, completes the financing checklist, and links a listing.

**Independent Test**: Create a Buyer lead and, in order, add a note, complete the checklist, and
link a listing — confirm status advances after each step without manual intervention.

### Tests for User Story 2

- [X] T010 [P] [US2] Test adding the first note to a New Buyer lead moves it to Contacted, in
      `tests/Feature/LeadTest.php`
- [X] T011 [P] [US2] Test completing all 3 checklist items on a Contacted Buyer lead moves it to
      Qualified, in `tests/Feature/LeadTest.php`
- [X] T012 [P] [US2] Test linking a listing to a Qualified Buyer lead moves it to Active Search, in
      `tests/Feature/LeadTest.php`
- [X] T013 [P] [US2] Test linking a listing to a New Buyer lead (no notes yet) does NOT move it to
      Active Search, in `tests/Feature/LeadTest.php`
- [X] T014 [P] [US2] Test completing the checklist before any note keeps status New; adding the
      first note afterward jumps status straight to Qualified in that same step, in
      `tests/Feature/LeadTest.php`
- [X] T015 [P] [US2] Test removing a lead's only note, or unchecking a checklist item, after the
      lead has already advanced does not move status backward, in `tests/Feature/LeadTest.php`

### Implementation for User Story 2

- [X] T016 [US2] Call `$lead->advanceBuyerFunnel()` at the end of `LeadController::storeNote()`
- [X] T017 [US2] Call `$lead->advanceBuyerFunnel()` at the end of `LeadController::store()` and
      `LeadController::update()`
- [X] T018 [US2] After syncing `lead_ids` in `ListingController::store()` and `update()`, call
      `advanceBuyerFunnel()` on every lead now linked to that listing
- [X] T019 [US2] Add the 3-item financing checklist to `resources/views/leads/edit.blade.php`,
      shown only when `type = buyer`
- [X] T020 [US2] Update the status `<select>` in `resources/views/leads/edit.blade.php` to show
      New/Contacted/Qualified/Active Search/Lost for Buyer leads, and the original six options
      (New/Qualified/Visited/Proposal/Closed/Lost) for every other type

**Checkpoint**: User Stories 1 and 2 both work independently.

---

## Phase 5: User Story 3 - Marking a lead Lost overrides the automated funnel (Priority: P2)

**Goal**: A lead marked Lost stays Lost, no matter what else happens to it, until an agent manually
changes it.

**Independent Test**: Mark leads Lost from several different starting statuses, then perform
actions that would normally advance the funnel, and confirm status stays Lost.

### Tests for User Story 3

- [X] T021 [P] [US3] Test marking a Buyer lead Lost from each of New, Contacted, Qualified, and
      Active Search, then performing that stage's triggering action (note added / checklist
      completed / listing linked), confirms status stays Lost, in `tests/Feature/LeadTest.php`
- [X] T022 [P] [US3] Test an agent can still manually change a Lost lead's status to something else,
      in `tests/Feature/LeadTest.php`

### Implementation for User Story 3

*No new implementation — the Lost guard clause was already built into `advanceBuyerFunnel()` in
T004. This phase exists to verify that behavior independently, per spec.md's own priority on it.*

**Checkpoint**: User Stories 1, 2, and 3 all work independently.

---

## Phase 6: User Story 4 - See lead counts by status on the Dashboard (Priority: P3)

**Goal**: An agent sees how many leads are in each status directly from the Dashboard.

**Independent Test**: Create leads with a mix of statuses and confirm the Dashboard shows the
correct count for each.

### Tests for User Story 4

- [X] T023 [P] [US4] Test the Dashboard shows the correct count of the logged-in agent's leads per
      status, in `tests/Feature/DashboardTest.php` (new file)
- [X] T024 [P] [US4] Test the Dashboard shows zero counts (not an error or blank section) when the
      agent has no leads yet, in `tests/Feature/DashboardTest.php`

### Implementation for User Story 4

- [X] T025 [US4] Add a lead-count-by-status query (`$request->user()->leads()->selectRaw('status,
      count(*) as total')->groupBy('status')->pluck('total', 'status')`) to the `/dashboard` route
      closure in `routes/web.php`
- [X] T026 [US4] Display the per-status counts in `resources/views/dashboard.blade.php`

**Checkpoint**: All 4 user stories are independently functional.

---

## Phase 7: Polish & Cross-Cutting Concerns

- [X] T027 [P] Run the full suite (`php artisan test`) and fix any regressions
- [X] T028 [P] Re-read all touched files against constitution Principles I (Simplicity) and V
      (Explainable Code); simplify anything that isn't easy to explain
- [ ] T029 Walk through `quickstart.md`'s manual validation steps end-to-end in the browser
- [X] T030 Update the System Blueprint and Data Blueprint artifacts to reflect the new `Lead` fields
      and the Buyer funnel

---

## Dependencies & Execution Order

- **Setup (Phase 1)**: No dependencies.
- **Foundational (Phase 2)**: Depends on Phase 1 — blocks every user story.
- **US1 (Phase 3)**: Depends only on Phase 2.
- **US2 (Phase 4)**: Depends only on Phase 2 (not on US1, though in practice `type` from US1 is what
  makes a lead eligible for the funnel — the two are easiest to build in order but not required to).
- **US3 (Phase 5)**: Depends only on Phase 2 (the Lost guard is part of T004); its tests exercise
  US2's trigger points too.
- **US4 (Phase 6)**: Depends only on Phase 2.
- **Polish (Phase 7)**: Depends on all 4 user stories being complete.

## Implementation Strategy

### MVP First

1. Phase 1 + Phase 2 (Setup + Foundational)
2. Phase 3 (US1 — Lead Type) → the smallest independently-shippable slice
3. **STOP and VALIDATE** US1 alone
4. Continue with US2 → US3 → US4 in priority order
