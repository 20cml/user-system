# Implementation Plan: Lead Funnel Automation

**Branch**: `006-lead-funnel-automation` | **Date**: 2026-09-16 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/006-lead-funnel-automation/spec.md`

**Note**: This template is filled in by the `/speckit-plan` command; its definition describes the execution workflow.

## Summary

Add a `type` classification to leads (Buyer, Seller, Investor, Renter, Landlord) and, for Buyer leads only, automatically advance `status` through New → Contacted → Qualified → Active Search as the agent does normal work (adding a note, completing a 3-item financing checklist, linking a listing) — without ever skipping a stage, moving backward, or overriding a lead the agent has manually marked Lost. The Dashboard gains a read-only summary of lead counts per status.

## Technical Context

**Language/Version**: PHP 8.3+ (Laravel 13), matching the rest of the project

**Primary Dependencies**: None new — Eloquent, Blade, existing `Lead`/`Listing`/`LeadNote` models and their controllers

**Storage**: MySQL, via a new migration adding columns to the existing `leads` table (no new tables)

**Testing**: PHPUnit / `php artisan test`, matching every prior feature

**Target Platform**: Web (server-rendered Blade), same as the rest of the app

**Project Type**: Web application (single Laravel app — no separate frontend/backend split)

**Performance Goals**: N/A — standard web request/response expectations, same as existing features

**Constraints**: N/A

**Scale/Scope**: Same scale as Feature 5 (an agent's own leads, up to ~200) — this feature adds fields and derived behavior to that same dataset, not a new one

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- **I. Simplicity & Convention over Configuration**: The 3 financing-checklist items are stored as plain boolean columns on `leads` (not a separate table/model) since they carry no metadata of their own and are always 1:1 with a Buyer lead. The forward-progression logic lives in one method on the `Lead` model, called from the few existing places that already touch notes/checklist/listings — no new architectural layer (service classes, events/listeners, observers) is introduced. PASS.
- **II. Security by Default**: `type` and the checklist fields are validated server-side via `LeadRequest`, same as every other Lead field; all reads/writes stay scoped to `auth()->user()->leads()`, same ownership pattern as the rest of the app. PASS.
- **III. Spec-Driven Development**: Following specify → plan → tasks → implement, one commit per stage. PASS.
- **IV. Test Coverage for Core Flows**: Each acceptance scenario in spec.md (auto-transitions, the no-skip/no-backward rule, Lost overriding automation, the Dashboard summary) gets a corresponding automated test. Addressed in tasks.md.
- **V. Explainable Code**: Centralizing the funnel rules in a single `Lead::advanceBuyerFunnel()` method (rather than duplicating the same forward-checks at each of the 3 call sites) keeps the whole rule set readable in one place and easy to explain. PASS.

## Project Structure

### Documentation (this feature)

```text
specs/006-lead-funnel-automation/
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
├── Models/
│   └── Lead.php                        # gains type + 3 checklist columns + advanceBuyerFunnel()
├── Http/
│   ├── Controllers/
│   │   ├── LeadController.php          # storeNote(), store(), update() call advanceBuyerFunnel()
│   │   └── ListingController.php       # store(), update() call advanceBuyerFunnel() on synced leads
│   └── Requests/
│       └── LeadRequest.php             # validates type + the 3 checklist booleans

database/
├── migrations/
│   └── 2026_xx_xx_xxxxxx_add_funnel_fields_to_leads_table.php

resources/
└── views/
    ├── leads/
    │   ├── create.blade.php            # gains type select
    │   └── edit.blade.php              # gains type select + financing checklist
    └── dashboard.blade.php             # gains lead-count-by-status summary

routes/
└── web.php                             # no new routes — reuses existing lead/listing routes

tests/
└── Feature/
    └── LeadTest.php                    # new tests for each acceptance scenario
```

**Structure Decision**: Single Laravel application, same layout as every prior feature — no new directories, no new controllers, no new routes. This feature extends the existing `Lead` model and its two call sites (`LeadController`, `ListingController`) rather than introducing a parallel structure.

## Complexity Tracking

*No violations — Constitution Check passed without exceptions.*
