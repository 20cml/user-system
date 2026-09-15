# Phase 0 Research: Lead CRM

## Decision: Lead↔Listing is a plain `belongsToMany`, no dedicated pivot model

**Rationale**: The relationship (a lead is interested in one or more listings) needs nothing beyond
the two foreign keys — no extra data like "date marked interested" is called for in the spec.
Laravel's built-in `belongsToMany` over a plain `lead_listing` pivot table is the framework-idiomatic
way to model this, matching constitution Principle I. Interest is set/changed via `sync()`, so the
edit form's list of checked listings always exactly reflects the submitted set.

**Alternatives considered**:
- **A dedicated `LeadListingInterest` model** (as sketched in the system-level architecture
  diagram) — would make sense once the relationship needs its own data (e.g., how interested, when
  first shown). Not needed yet; the plain pivot can be upgraded to a full model later without
  changing the `Lead`/`Listing` models themselves if that becomes necessary.

## Decision: Linking is a searchable "tag picker," not a checkbox list of every record

**Rationale**: A checkbox per listing (or per lead) doesn't scale — spec SC-004 already anticipates
an agent managing up to ~200 listings, and a 200-item checkbox list is unusable. Instead, both
directions of the relationship use the same small, reusable component
(`resources/views/partials/tag-picker.blade.php`): the agent types into a search box, matching
results appear (via a small first-party search endpoint scoped to their own records), clicking one
adds it as a removable "chip," and each chip is a hidden `{field}_ids[]` input — so the form still
submits the exact same array shape the controller already expected. This also made linking
symmetric: an agent can link from the Lead's page (search listings by address or ID) or from the
Listing's page (search leads by name) — same underlying `lead_listing` pivot either way.

**Alternatives considered**:
- **A checkbox list of the agent's own records** — the original design; simple, but doesn't scale
  and only worked from the Lead's side. Replaced after direct user feedback during manual testing.
- **A single server-rendered `<select multiple>` with no search** — scales slightly better than
  checkboxes but still requires scrolling a long list rather than typing to narrow it down.

## Decision: The server re-validates that every linked listing belongs to the agent

**Rationale**: A lead's `edit` form will show a search-and-select picker for the agent's own listings, but nothing
stops a manipulated request from submitting a listing ID that belongs to someone else. The
`LeadRequest` validates every submitted listing ID against `auth()->user()->listings()` before
`sync()` runs, so an agent can never link a lead to another agent's listing even by tampering with
the request — consistent with constitution Principle II (never trust client-side restrictions
alone).

**Alternatives considered**:
- **Trusting the checkbox list to only ever contain the agent's own listings** — true in the normal
  UI, but a manipulated request could submit any ID. Rejected; validating server-side costs one
  extra rule and closes the gap entirely.

## Decision: Notes are a separate `LeadNote` model, added via their own small action

**Rationale**: Unlike a listing's fields (which get replaced wholesale on save), notes accumulate —
an agent might add dozens over a lead's lifetime. A `hasMany` `LeadNote` model, with its own
`POST /leads/{lead}/notes` route, keeps adding a note independent of editing the rest of the lead's
details, and keeps `LeadController::update()` from having to handle an ever-growing array of notes
in a single request.

**Alternatives considered**:
- **A single `notes` text column on `Lead`, appended to** — simpler schema, but loses per-note
  timestamps and ordering (spec FR-008 explicitly wants each note shown with when it was added, most
  recent first), and would require string-parsing to separate entries. Rejected.

## Decision: `/leads` gets a name search box, reusing `/api/leads-search` as-is

**Rationale**: With up to ~200 leads (spec SC-004), finding one by scrolling the list stops being
practical. `/api/leads-search` already exists (built for the tag picker) and already searches the
agent's own leads by name — the index page just needed a small search-as-you-type input that
renders each match as a link straight to `/leads/{id}/edit`, reusing the endpoint rather than
building a new one.

**Alternatives considered**:
- **A live-filtering table** (hide/show rows as the agent types) — would work without a network
  request, but doesn't scale to 200 rows loaded into the page at once the way a server-side search
  does, and duplicates matching logic that already exists server-side.

## Decision: `first_name`/`last_name`, not a single `name` field

**Rationale**: Split after direct user feedback, for consistency with how `User` (Feature 1)
already separates first and last name. Only `first_name` is required — an agent may genuinely only
catch someone's first name at first (e.g., meeting them briefly at an open house), so requiring
`last_name` too would block adding a lead the agent doesn't have complete information on yet.
`Lead::name` stays available as a computed accessor (`trim("{first_name} {last_name}")`) so
existing display/search code didn't need to change — it already reads `$lead->name`.

**Alternatives considered**:
- **Keeping a single `name` field** — simpler schema, but inconsistent with `User`'s convention and
  loses the ability to (later) sort or address a lead by last name specifically.
- **Requiring both `first_name` and `last_name`** — matches `User`'s validation, but a lead is
  often captured with incomplete information; forcing a last name before saving would lose leads
  the agent hasn't fully identified yet.

## Decision: Notes can be deleted, but not edited

**Rationale**: Manual testing showed a real need to remove a note added by mistake — the original
"fully append-only" design (no edit, no delete) turned out to be stricter than agents actually want.
Deletion is added (FR-012), authorized through the *lead's* own `LeadPolicy::update` (a note has no
`user_id` of its own — ownership flows through the lead it belongs to, so no separate `LeadNotePolicy`
is needed), with the route verifying the note actually belongs to the lead in the URL
(`$note->lead_id === $lead->id`) before deleting. Editing a note's text is still left out — a note
is a record of what happened, which is less useful if it can be silently rewritten later; deleting
an entire wrong note is a cleaner fix than editing it into something else.

**Alternatives considered**:
- **Fully append-only (no delete either)** — the original design; rejected after direct user
  feedback during manual testing showed it was too strict for real use (e.g., a duplicate or
  mistaken note with no way to remove it).
- **Allowing notes to be edited too** — would let a "record of what happened" be quietly rewritten
  after the fact, which is a different (weaker) guarantee than what a call/interaction log is
  supposed to provide. Delete-and-recreate is simpler and keeps that guarantee for whatever remains.

## Decision: No restriction on lead status transitions

**Rationale**: Same reasoning as Listings' status (see `specs/004-property-listings/research.md`)
— spec FR-006 only asks that an agent can change status among the six values, not that the funnel
only moves forward. A lead marked "lost" might come back after all; allowing free movement between
statuses matches how agents actually work.

**Alternatives considered**:
- **A strict one-way funnel (new → qualified → visited → proposal → closed/lost only)** — would
  need extra validation logic and doesn't match a real scenario (a lead going quiet then
  re-engaging). Rejected.

## Decision: Deleting a lead is a hard delete, distinct from "closed"/"lost", mirroring Listings

**Rationale**: Consistent with how Property Listings ended up handling this (FR-009 there, FR-011
here): closing/losing a lead keeps it as history; deleting it removes the row (and its notes,
cascade-deleted) permanently. Same `LeadPolicy::delete` pattern, same confirmation-before-submit UX
already established for Listings.

**Alternatives considered**:
- **No delete at all, only status** — was Listings' original design too, until manual testing
  showed a real need for permanent removal (e.g., a lead added by mistake). Building it in from the
  start this time, now that the pattern is established.
