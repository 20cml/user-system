# Implementation Plan: Lead CRM

**Branch**: `005-lead-crm` | **Date**: 2026-09-13 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/005-lead-crm/spec.md`

## Summary

Agents need to track people interested in the properties they've listed. This feature adds a
`Lead` model owned by the agent who creates it, linked to any number of that agent's own `Listing`
records (many-to-many), a status field for the sales funnel, and an append-only log of timestamped
notes (`LeadNote`) for interactions over time.

## Technical Context

**Language/Version**: PHP 8.3+, Laravel 13

**Primary Dependencies**: Laravel (Eloquent, Blade, validation, routing, policies, `belongsToMany` for the lead↔listing relationship) — all already installed, no new dependency required

**Storage**: MySQL (new `leads`, `lead_listing` pivot, and `lead_notes` tables)

**Testing**: PHPUnit, Laravel's `RefreshDatabase` Feature tests (same approach as Features 1–4)

**Target Platform**: Web (server-rendered Blade views)

**Project Type**: Single Laravel web application (existing monolith)

**Performance Goals**: Standard web request/response times; no special performance target beyond what Laravel/MySQL provide by default

**Constraints**: None beyond the existing stack — no new services, queues, or external APIs

**Scale/Scope**: Two new entities (`Lead`, `LeadNote`) plus a plain pivot table, one controller, one policy; matches spec's SC-004 (an agent managing ~200 leads without noticeable slowdown, well within a simple indexed query)

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Check |
|---|---|
| I. Simplicity & Convention over Configuration | Uses standard Eloquent models, a resource-style controller, and Blade views. The lead↔listing relationship uses Laravel's built-in `belongsToMany` over a plain pivot table — no dedicated pivot model, since no extra data is stored on the relationship itself. |
| II. Security by Default | All lead routes require `auth`, `verified`, `profile.complete`, `no-cache` (same stack as Listings/Dashboard/Profile). Ownership enforced by a `LeadPolicy`. When linking a lead to listings, the server validates every submitted listing ID actually belongs to the authenticated agent — never trusts the client to only submit its own listings. |
| III. Spec-Driven Development | This plan follows `spec.md`, produced by `/speckit-specify`; `/speckit-tasks` and `/speckit-implement` come next. |
| IV. Test Coverage for Core Flows | Applies to this feature's core flows — create, link to listings, change status, add a note, delete, and the ownership boundary — each covered by an automated test. |
| V. Explainable Code | Plain CRUD plus one append-only child resource (notes). Nothing here needs a comment to justify its existence. |

No unjustified violations — Complexity Tracking table not needed.

**Post-Phase 1 re-check**: `data-model.md`, `contracts/`, and `quickstart.md` don't introduce
anything beyond what this table already covers — still standard Eloquent models, a plain pivot, one
policy, one Form Request, no new dependency. Gate still passes.

## Project Structure

### Documentation (this feature)

```text
specs/005-lead-crm/
├── plan.md              # This file
├── research.md          # Phase 0 output
├── data-model.md        # Phase 1 output
├── quickstart.md         # Phase 1 output
├── contracts/            # Phase 1 output
└── tasks.md              # Phase 2 output (/speckit-tasks — not created by this command)
```

### Source Code (repository root)

```text
app/
├── Models/
│   ├── Lead.php                              # new — belongsTo User, belongsToMany Listing, hasMany LeadNote
│   └── LeadNote.php                          # new — belongsTo Lead
├── Http/
│   ├── Controllers/
│   │   └── LeadController.php                # new — index, create, store, edit, update, destroy, storeNote
│   └── Requests/
│       └── LeadRequest.php                    # new — validates name/phone/email/status/listing_ids
├── Policies/
│   └── LeadPolicy.php                         # new — ownership check (view/update/delete only own leads)

database/
├── migrations/
│   ├── ..._create_leads_table.php             # new
│   ├── ..._create_lead_listing_table.php      # new — pivot, no model
│   └── ..._create_lead_notes_table.php        # new
└── factories/
    ├── LeadFactory.php                         # new
    └── LeadNoteFactory.php                     # new

resources/views/leads/
├── index.blade.php                            # new — agent's own leads
├── create.blade.php                           # new
└── edit.blade.php                              # new — also serves as the detail view: status, listing links, notes

routes/web.php                                 # add leads routes (auth + verified + profile.complete + no-cache)

tests/Feature/
└── LeadTest.php                                # new — create, link listings, status change, notes, delete, ownership boundary
```

**Structure Decision**: Slots into the existing Laravel monolith the same way Listings (Feature 4)
did. No `show.blade.php` — the edit page doubles as the detail view, same reasoning as Listings
(only the owning agent ever sees a lead).

## Complexity Tracking

*No violations to justify — table intentionally left empty.*
