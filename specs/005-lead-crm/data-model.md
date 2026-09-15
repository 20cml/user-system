# Phase 1 Data Model: Lead CRM

## Entity: Lead

Represents a person who has shown interest in one or more of an agent's properties (from spec.md's
Key Entities section).

| Field | Type | Rules |
|---|---|---|
| `id` | bigint, primary key | auto-increment |
| `user_id` | bigint, foreign key → `users.id` | required; the agent who owns this lead |
| `first_name` | string | required |
| `last_name` | string, nullable | optional |
| `phone` | string, nullable | optional |
| `email` | string, nullable | optional |
| `status` | string | required; `new`, `qualified`, `visited`, `proposal`, `closed`, or `lost`; defaults to `new` on creation |
| `created_at` / `updated_at` | timestamp | standard Eloquent timestamps |

## Entity: LeadNote

Represents a timestamped note logging an interaction with a lead (from spec.md's Key Entities
section).

| Field | Type | Rules |
|---|---|---|
| `id` | bigint, primary key | auto-increment |
| `lead_id` | bigint, foreign key → `leads.id` | required; the lead this note belongs to |
| `body` | text | required; the note's content |
| `created_at` / `updated_at` | timestamp | standard Eloquent timestamps; `created_at` is the date shown to the agent |

## Pivot: `lead_listing`

Plain pivot table (no dedicated Eloquent model — see research.md), joining `leads` and `listings`.

| Field | Type | Rules |
|---|---|---|
| `lead_id` | bigint, foreign key → `leads.id` | required |
| `listing_id` | bigint, foreign key → `listings.id` | required; must belong to the same agent who owns the lead (enforced in `LeadRequest`, not at the database level) |

### Relationships

- `Lead belongsTo User` (via `user_id`) — one agent can have many `Lead` records; a `Lead` belongs
  to exactly one agent.
- `Lead belongsToMany Listing` (via `lead_listing`) — a `Lead` may be linked to any number of the
  agent's own `Listing` records, and a `Listing` may have any number of interested `Lead`s.
- `Lead hasMany LeadNote` (via `lead_id`) — a `Lead` may have any number of notes, each belonging to
  exactly one `Lead`.

### State: lead status

A lead is in exactly one of six states, held in `status`:

```text
new ⇄ qualified ⇄ visited ⇄ proposal ⇄ closed
                                      ⇄ lost
```

Movement between any of the six values is allowed in either direction (see research.md) — the
agent sets whichever status matches reality when they edit the lead.

### Validation rules (from spec.md Functional Requirements)

- `first_name`: required (FR-002)
- `last_name`, `phone`, `email`: optional
- `status`: one of `new`, `qualified`, `visited`, `proposal`, `closed`, `lost`; set to `new`
  automatically on creation, not required as input when creating (FR-003, FR-006)
- `listing_ids` (not a column — the set of `Listing` IDs to sync via the pivot): each must belong
  to the authenticated agent (FR-007)
- `body` (on `LeadNote`): required, non-empty (FR-008)

No cap is placed on the number of listings a lead can be linked to, or the number of notes a lead
can accumulate.

`Lead::name` is a computed accessor (`trim("{first_name} {last_name}")`), not a database column —
it exists so display code (the index list, search results) can read `$lead->name` without knowing
the underlying fields are split, and correctly drops the trailing space when `last_name` is empty.
