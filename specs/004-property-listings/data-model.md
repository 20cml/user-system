# Phase 1 Data Model: Property Listings

## Entity: Listing

Represents a property an agent has available to sell or rent (from spec.md's Key Entities section).

| Field | Type | Rules |
|---|---|---|
| `id` | bigint, primary key | auto-increment |
| `user_id` | bigint, foreign key → `users.id` | required; the agent who owns this listing |
| `source` | string | `manual`, `import`, or `api`; this feature always writes `manual` — the other values exist for a future feature |
| `listing_type` | string | required; `sale` or `rent` |
| `property_type` | string | required; `house`, `apartment`, `land`, or `commercial` |
| `status` | string | required; `available`, `pending`, or `closed`; defaults to `available` on creation |
| `address_line` | string | required |
| `city` | string | required |
| `state_province` | string | required |
| `postal_code` | string | required |
| `country` | string | required; `CA` or `US` — the property's own country, entered as part of the address (assisted by autocomplete, same as the profile page) |
| `price` | decimal | required; must be greater than zero |
| `currency` | string | `CAD` or `USD`; set automatically from the *agent's own* profile `country` (not the listing's `country`), never accepted as direct input |
| `area_sqm` | decimal, nullable | property size, in square meters |
| `description` | text, nullable | free-form description |
| `created_at` / `updated_at` | timestamp | standard Eloquent timestamps |

## Entity: ListingPhoto

Represents an image attached to a `Listing` (from spec.md's Key Entities section).

| Field | Type | Rules |
|---|---|---|
| `id` | bigint, primary key | auto-increment |
| `listing_id` | bigint, foreign key → `listings.id` | required; the listing this photo belongs to |
| `path` | string | required; where the file is stored on the server's local disk |
| `sort_order` | integer | required; determines display order among a listing's photos (0-indexed) |
| `created_at` / `updated_at` | timestamp | standard Eloquent timestamps |

### Relationships

- `Listing belongsTo User` (via `user_id`) — one agent (`User`) can have many `Listing` records;
  a `Listing` belongs to exactly one agent.
- `Listing hasMany ListingPhoto` (via `listing_id`) — a `Listing` may have up to 3 `ListingPhoto`
  records (FR-011, FR-012); a `ListingPhoto` belongs to exactly one `Listing`.

### State: listing status

A listing is in exactly one of three states, held in `status`:

```text
[Available] <--> [Pending] <--> [Closed]
```

Movement between all three is allowed in either direction (see research.md) — the agent sets
whichever status matches reality when they edit the listing.

### Validation rules (from spec.md Functional Requirements)

- `address_line`, `city`, `state_province`, `postal_code`, `country`: required (FR-002, FR-015)
- `price`: required, numeric, greater than zero (FR-002, FR-003)
- `listing_type`: required, one of `sale` or `rent` (FR-001, FR-002)
- `property_type`: required, one of `house`, `apartment`, `land`, or `commercial` (FR-001, FR-002)
- `currency`: not submitted by the agent — set from `auth()->user()->country` (FR-010)
- `status`: one of `available`, `pending`, `closed`; set to `available` automatically on creation,
  not required as input when creating (FR-004, FR-007)
- `area_sqm`, `description`: optional
- `photos`: at most 3 image files may be attached to a listing at any time (FR-011, FR-012)

A listing's `created_at` is shown to the agent as the date it was added, and the list of listings
is ordered by `created_at` descending — newest first (FR-016). No separate "date added" field is
needed since Eloquent already tracks `created_at` on every record.

### Filtering (FR-017, FR-018)

The index list can optionally be narrowed by `status`, `listing_type`, `property_type`, and/or a
minimum/maximum `price` — combinable in any mix. These are query parameters on `GET /listings`,
not stored data; no new column or entity is introduced. Omitting all of them returns the full list,
unfiltered.

### State: photo ordering

A `Listing`'s photos each carry a `sort_order` (their position). Removing a photo (FR-013) deletes
its file and its `ListingPhoto` row; changing the order (FR-014) reassigns `sort_order` values
among the listing's remaining photos.
