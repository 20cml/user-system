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

## Decision: No restriction on status transitions

**Rationale**: Spec FR-007 asks only that an agent can change status among the three values — it
doesn't require enforcing a one-way workflow (e.g., blocking a move from `pending` back to
`available`). Real-world deals do fall through, so allowing free movement between the three
statuses matches how agents actually work.

**Alternatives considered**:
- **A strict one-way state machine (`available` → `pending` → `closed` only)** — would need extra
  validation logic and doesn't match a real scenario (a pending offer that falls through). Rejected.
