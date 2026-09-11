# Phase 1 Data Model: Property Listings

## Entity: Listing

Represents a property an agent has available to sell or rent (from spec.md's Key Entities section).

| Field | Type | Rules |
|---|---|---|
| `id` | bigint, primary key | auto-increment |
| `user_id` | bigint, foreign key → `users.id` | required; the agent who owns this listing |
| `source` | string | `manual`, `import`, or `api`; this feature always writes `manual` — the other values exist for a future feature |
| `listing_type` | string | required; `sale` or `rent` |
| `status` | string | required; `available`, `pending`, or `closed`; defaults to `available` on creation |
| `address_line` | string | required |
| `city` | string | required |
| `state_province` | string | required |
| `postal_code` | string | required |
| `country` | string | required |
| `price` | decimal | required; must be greater than zero |
| `bedrooms` | integer, nullable | number of bedrooms |
| `bathrooms` | integer, nullable | number of bathrooms |
| `area_sqm` | decimal, nullable | property size, in square meters |
| `description` | text, nullable | free-form description |
| `created_at` / `updated_at` | timestamp | standard Eloquent timestamps |

### Relationship

- `Listing belongsTo User` (via `user_id`) — one agent (`User`) can have many `Listing` records;
  a `Listing` belongs to exactly one agent.

### State: listing status

A listing is in exactly one of three states, held in `status`:

```text
[Available] <--> [Pending] <--> [Closed]
```

Movement between all three is allowed in either direction (see research.md) — the agent sets
whichever status matches reality when they edit the listing.

### Validation rules (from spec.md Functional Requirements)

- `address_line`, `city`, `state_province`, `postal_code`, `country`: required (FR-002)
- `price`: required, numeric, greater than zero (FR-002, FR-003)
- `listing_type`: required, one of `sale` or `rent` (FR-001, FR-002)
- `status`: one of `available`, `pending`, `closed`; set to `available` automatically on creation,
  not required as input when creating (FR-004, FR-007)
- `bedrooms`, `bathrooms`, `area_sqm`, `description`: optional

No other entities are introduced by this feature.
