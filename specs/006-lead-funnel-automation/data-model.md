# Phase 1 Data Model: Lead Funnel Automation

## Lead (extended)

Existing fields (from Feature 5) are unchanged. This feature adds:

| Column | Type | Notes |
|---|---|---|
| `type` | `string`, nullable | One of `buyer`, `seller`, `investor`, `renter`, `landlord`, or `null` (existing leads, or a lead the agent hasn't classified yet). A `null` type is treated as not-Buyer — the funnel automation never applies to it. |
| `financing_preapproval` | `boolean`, default `false` | Financing checklist item 1. Only meaningful for `type = buyer`. |
| `financing_income_proof` | `boolean`, default `false` | Financing checklist item 2. |
| `financing_id_document` | `boolean`, default `false` | Financing checklist item 3. |

### Status values

The existing `status` column (`string`, unchanged type) gains two new valid values for Buyer leads,
on top of the six that already exist:

| Value | Introduced by | Used by |
|---|---|---|
| `new` | Feature 5 | All lead types (default on creation) |
| `contacted` | **Feature 6** | Buyer only — reached automatically |
| `qualified` | Feature 5 | All types — reached automatically for Buyer, manually for others |
| `active_search` | **Feature 6** | Buyer only — reached automatically |
| `visited` | Feature 5 | Non-Buyer types (manual) |
| `proposal` | Feature 5 | Non-Buyer types (manual) |
| `closed` | Feature 5 | All types (manual) |
| `lost` | Feature 5 | All types (manual, always available, overrides automation) |

Validation (`LeadRequest`) accepts all 8 values regardless of `type` — the server does not reject a
non-Buyer lead being manually set to `contacted`, since constitution Principle I favors the simplest
rule that satisfies the spec, and spec.md doesn't call for cross-field validation here. The
**edit form**, however, only shows the status options that make sense for the lead's `type`:
Buyer leads see New / Contacted / Qualified / Active Search / Lost; every other type sees the
original New / Qualified / Visited / Proposal / Closed / Lost (unchanged from Feature 5).

### State transitions (automatic, Buyer leads only)

```
New ──(first note added)──> Contacted ──(financing checklist complete)──> Qualified ──(listing linked)──> Active Search
 │                              │                                            │                                │
 └──────────────────────────────┴───────────── (agent marks Lost, any stage) ┴────────────────────────────────┘
                                                          ↓
                                                        Lost
```

- Transitions only ever move forward (never skip, never reverse) — see `research.md` for the
  `Lead::advanceBuyerFunnel()` method that enforces this.
- `Lost` is reachable manually from any stage and, once set, blocks all of the automatic transitions
  above until an agent manually changes the status away from `Lost`.
- Manual status changes by the agent are never blocked by this feature, for any lead type.

## Relationships

No new relationships. `advanceBuyerFunnel()` reads the lead's existing `notes()` (HasMany) and
`listings()` (BelongsToMany) relationships — both already exist from Feature 5.

## Validation rules (`LeadRequest`)

- `type`: `nullable|in:buyer,seller,investor,renter,landlord`
- `financing_preapproval`, `financing_income_proof`, `financing_id_document`: `boolean` (defaults to
  `false` when the checkbox is unchecked and thus absent from the request, same pattern already used
  elsewhere in this app for checkbox-backed booleans)
- `status`: unchanged validation (`sometimes|in:new,contacted,qualified,active_search,visited,proposal,closed,lost`)
  — the list of accepted values grows from 6 to 8; still accepted regardless of `type`.
