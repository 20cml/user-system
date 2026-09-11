# Implementation Plan: Property Listings

**Branch**: `004-property-listings` | **Date**: 2026-09-10 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/004-property-listings/spec.md`

## Summary

Agents need a place to track the properties they have available to sell or rent. This feature adds
a `Listing` model owned by the agent who creates it, a set of Blade pages to create/view/edit
listings, and a status field (`available`/`pending`/`closed`) the agent updates as a deal
progresses. Access is scoped so an agent only ever sees and edits their own listings.

## Technical Context

**Language/Version**: PHP 8.3+, Laravel 13

**Primary Dependencies**: Laravel (Eloquent, Blade, validation, routing, policies), Tailwind CSS, Alpine.js — all already installed, no new dependency required

**Storage**: MySQL (new `listings` table)

**Testing**: PHPUnit, Laravel's `RefreshDatabase` Feature tests (same approach as Features 1–3)

**Target Platform**: Web (server-rendered Blade views)

**Project Type**: Single Laravel web application (existing monolith)

**Performance Goals**: Standard web request/response times; no special performance target beyond what Laravel/MySQL provide by default

**Constraints**: None beyond the existing stack — no new services, queues, or external APIs

**Scale/Scope**: One new entity (`Listing`), one controller, one policy, a handful of Blade views; matches spec's SC-004 (an agent managing ~200 listings without noticeable slowdown, well within a simple indexed query)

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Check |
|---|---|
| I. Simplicity & Convention over Configuration | Uses a standard Eloquent model, a resource-style controller, and Blade views — no new abstraction layer. Authorization uses a Laravel Policy (`ListingPolicy`), the framework's built-in mechanism for "can this user act on this record" checks. |
| II. Security by Default | All listing routes require `auth` (and `verified`/`profile.complete`, matching `/dashboard`'s existing gate). All fields are validated server-side via a Form Request. Ownership is enforced by `ListingPolicy`, not by client-side hiding — an agent cannot reach another agent's listing even by guessing its URL. |
| III. Spec-Driven Development | This plan follows `spec.md`, produced by `/speckit-specify`; `/speckit-tasks` and `/speckit-implement` come next. |
| IV. Test Coverage for Core Flows | Applies to this feature's core flows — create, edit, change status, and the ownership boundary — each covered by an automated test. |
| V. Explainable Code | Plain CRUD: one model, one controller, one policy, one Form Request. Nothing here needs a comment to justify its existence. |

No unjustified violations — Complexity Tracking table not needed.

**Post-Phase 1 re-check**: `data-model.md`, `contracts/`, and `quickstart.md` don't introduce
anything beyond what this table already covers — still a single Eloquent model, one policy, one
Form Request, no new dependency. Gate still passes.

## Project Structure

### Documentation (this feature)

```text
specs/004-property-listings/
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
│   └── Listing.php                          # new
├── Http/
│   ├── Controllers/
│   │   └── ListingController.php            # new — index, create, store, edit, update
│   └── Requests/
│       └── ListingRequest.php                # new — validates create/update input
├── Policies/
│   └── ListingPolicy.php                     # new — ownership check (view/update only own listings)

database/
├── migrations/
│   └── ..._create_listings_table.php         # new
└── factories/
    └── ListingFactory.php                    # new

resources/views/listings/
├── index.blade.php                           # new — agent's own listings
├── create.blade.php                          # new
└── edit.blade.php                            # new — also serves as the detail view

routes/web.php                                # add listings routes (auth + verified + profile.complete)

tests/Feature/
└── ListingTest.php                           # new — create, edit, status change, ownership boundary
```

**Structure Decision**: This is a single existing Laravel monolith (not a frontend/backend split) — the
new feature slots into the existing `app/`, `database/`, `resources/views/`, and `tests/Feature/`
directories the same way Features 1–3 did. No `show.blade.php` — the edit page doubles as the
detail view since only the owning agent ever sees a listing.

## Complexity Tracking

*No violations to justify — table intentionally left empty.*
