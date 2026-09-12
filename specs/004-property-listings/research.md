# Phase 0 Research: Property Listings

## Decision: Enforce ownership with a Laravel Policy, not manual `if` checks in the controller

**Rationale**: Laravel's built-in authorization layer (Policies) is the framework-idiomatic way to
answer "can this user act on this record?" — `$this->authorize('update', $listing)` in the
controller, backed by a `ListingPolicy::update()` method that checks `$listing->user_id ===
$user->id`. This satisfies spec FR-006/FR-008 (an agent can't view or edit another agent's listing)
without scattering ownership checks across every controller method, and matches constitution
Principle I (prefer the framework's built-in solutions over custom patterns).

**Alternatives considered**:
- **Manual `if ($listing->user_id !== auth()->id()) abort(403)` in each method** — works, but
  duplicates the same check in `edit`, `update`, and anywhere else a single listing is loaded.
  Rejected in favor of the one-place Policy.

## Decision: A single `update` action handles both detail edits and status changes

**Rationale**: Spec FR-007 ("change a listing's status among available/pending/closed") doesn't
require a separate endpoint from general editing — a status field alongside address/price/etc. on
the same edit form is simpler and matches how Feature 3's `ProfileController` handles multiple
fields through one `update` action.

**Alternatives considered**:
- **A dedicated `PATCH /listings/{listing}/status` endpoint** — would make sense if status changes
  needed different validation or authorization than the rest of the fields, but nothing in the spec
  calls for that distinction. Rejected as unnecessary complexity for this version.

## Decision: No separate `show` (detail) page — the edit page serves that purpose

**Rationale**: Since only the owning agent ever sees a listing (per FR-008), there's no read-only
audience that would need a `show` view distinct from `edit`. Skipping it avoids a near-duplicate
Blade view with no one to use it.

**Alternatives considered**:
- **Building `index` → `show` → `edit` as three separate views** — the more common public-facing
  pattern (e.g., a buyer browsing listings), but that audience doesn't exist yet in this feature's
  scope. Rejected for now; revisit if/when a client-facing view is ever built.

## Decision: Include a `source` column on `listings` now, hardcoded to `manual`

**Rationale**: The system-level architecture already anticipates listings arriving by more than one
channel in the future (manual entry, import, external API — see the project's architecture
diagrams). Adding the column now, even though this feature only ever writes `'manual'` to it, means
a future feature can add new values without an extra migration touching this table's shape.

**Alternatives considered**:
- **Adding `source` only when an import/API feature is actually built** — simpler for this feature
  in isolation, but would require an `ALTER TABLE` migration later purely to add back a column that
  was already designed for. Included now since the cost of one extra column is negligible and the
  need is already confirmed, not speculative.

## Decision: `property_type` instead of `bedrooms`/`bathrooms`

**Rationale**: Bedroom and bathroom counts only make sense for residential properties — a land or
commercial listing has neither. A `property_type` field (`house`, `apartment`, `land`,
`commercial`) categorizes the listing without assuming every listing is residential, and keeps the
schema simpler than tracking room counts that don't apply to every type.

**Alternatives considered**:
- **Keeping `bedrooms`/`bathrooms` as nullable, alongside `property_type`** — would let residential
  listings carry more detail, but adds fields that don't apply to two of the four property types.
  Rejected for this version in favor of the simpler set; can be added back later if agents need it.

## Decision: A listing's own `country` reflects the property's address, entered via autocomplete

**Rationale**: `country` is part of a listing's address, the same way it's part of a user's address
on the profile page — it describes where the *property* is, not who's managing it. The same
`/api/address-suggestions` autocomplete used on the profile form fills it in (along with
`city`/`state_province`/`postal_code`) as the agent types the postal/zip code, but it stays a
regular editable field, just like the profile page's equivalent fields.

**Alternatives considered**:
- **Copying it from the agent's own profile country** — considered briefly, but conflates "where
  the agent operates" with "where this specific property is," which aren't guaranteed to be the
  same and shouldn't be forced together.

## Decision: `currency` is derived from the *agent's* profile country, not the listing's own country

**Rationale**: Listings can be in Canada or the United States, so a bare `price` number is
ambiguous (CAD or USD?). Rather than adding a currency field the agent fills in by hand, the
controller sets it automatically from the *agent's* own profile country (`CA` → `CAD`,
`US` → `USD`) — currency reflects how the agent conducts business, not the specific property's
location, so a Canada-based agent's listings are always priced in CAD even for a property that
happens to be in the U.S.

**Alternatives considered**:
- **Deriving `currency` from the listing's own `country`** — would make a single agent's listings
  potentially show different currencies depending on the property, which is more confusing (for the
  agent tracking their own numbers) than useful.
- **A free-entry `currency` field** — more flexible in theory, but nothing in the spec calls for a
  currency the agent's own profile country doesn't already determine.

## Decision: Photos are stored on the local filesystem via Laravel's `Storage` facade

**Rationale**: Laravel's built-in `Storage::disk('public')` handles file uploads without adding any
new package or third-party service — consistent with constitution Principle I (prefer the
framework's built-in solutions) and the "no new infrastructure unless justified" constraint. At
most 3 photos per listing (FR-011, FR-012) keeps storage needs small enough that local disk is
plainly sufficient for this project's scale.

**Alternatives considered**:
- **A cloud storage provider (e.g., S3)** — the more common production choice, since it doesn't tie
  files to one server's disk. Rejected for now as unjustified infrastructure for a project running
  on a single local/dev server; revisit if this ever needs to run on infrastructure where the disk
  isn't persistent (e.g., some hosting platforms).

## Decision: Photo reordering via a "position" field per photo, not drag-and-drop

**Rationale**: A numbered position field (1st, 2nd, 3rd) per existing photo, submitted with the
same edit form, reassigns each photo's `sort_order` on save — no new JavaScript library needed
(Alpine.js, already in the project, isn't suited to full drag-and-drop without a plugin). Simple to
explain, simple to test.

**Alternatives considered**:
- **Drag-and-drop reordering** — nicer to use, but requires a JS library this project doesn't have
  and constitution Principle I discourages adding one without a clear requirement for it. Revisit
  if agents find the position field cumbersome in practice.

## Decision: Filters are query parameters handled in `index()`, not a separate endpoint or form submission

**Rationale**: `GET /listings?status=pending&max_price=500000` is a normal, bookmarkable/shareable
GET request — filters are just optional query parameters `ListingController::index()` reads and
applies as `where()` clauses on top of `auth()->user()->listings()`, each only added when present.
No new route, controller, or request class needed.

**Alternatives considered**:
- **A `POST` "search" endpoint** — unnecessary; nothing here mutates data or needs a request body,
  and GET means the filtered view is a normal, linkable URL.

## Decision: No restriction on status transitions

**Rationale**: Spec FR-007 asks only that an agent can change status among the three values — it
doesn't require enforcing a one-way workflow (e.g., blocking a move from `pending` back to
`available`). Real-world deals do fall through, so allowing free movement between the three
statuses matches how agents actually work.

**Alternatives considered**:
- **A strict one-way state machine (`available` → `pending` → `closed` only)** — would need extra
  validation logic and doesn't match a real scenario (a pending offer that falls through). Rejected.
