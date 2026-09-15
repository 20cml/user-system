# Contracts: HTTP Routes for Lead CRM

This is a server-rendered Blade app, not a JSON API — its "contract" is the set of HTTP routes it
exposes and what each one does. All routes below are new.

| Method | Path | Access | Purpose | Success | Failure |
|---|---|---|---|---|---|
| GET | `/leads` | `auth`, `verified`, `profile.complete`, `no-cache` | List the logged-in agent's own leads, newest first, with a name search box (reusing `/api/leads-search`) that links straight to a match's edit page | 200, only leads where `user_id` matches the logged-in agent | — |
| GET | `/leads/create` | `auth`, `verified`, `profile.complete`, `no-cache` | Show the "add a lead" form | 200, blank form, with a search-and-select picker to link the agent's own listings | — |
| POST | `/leads` | `auth`, `verified`, `profile.complete`, `no-cache` | Create a new lead owned by the logged-in agent, optionally linked to some of their listings | Redirects to `/leads` with the new lead visible, status defaulted to `new` (FR-003) | 422 + validation errors for a missing first name or a submitted listing ID that isn't the agent's own |
| GET | `/leads/{lead}/edit` | `auth`, `verified`, `profile.complete`, `no-cache`, **`LeadPolicy::update`** | Show the edit form for one lead — details, status, linked listings (as removable chips), and its notes | 200, only if `{lead}` belongs to the logged-in agent | 403 if `{lead}` belongs to a different agent (FR-009) |
| PATCH | `/leads/{lead}` | `auth`, `verified`, `profile.complete`, `no-cache`, **`LeadPolicy::update`** | Save changes to a lead's details, status, and/or linked listings | Redirects to `/leads` with the updated values (FR-005, FR-006, FR-007) | 422 + validation errors; 403 if `{lead}` belongs to a different agent |
| DELETE | `/leads/{lead}` | `auth`, `verified`, `profile.complete`, `no-cache`, **`LeadPolicy::delete`** | Permanently delete a lead (and its notes) | Redirects to `/leads`; the lead is gone (FR-011) | 403 if `{lead}` belongs to a different agent |
| POST | `/leads/{lead}/notes` | `auth`, `verified`, `profile.complete`, `no-cache`, **`LeadPolicy::update`** | Add a timestamped note to a lead | Redirects back to `/leads/{lead}/edit` with the new note visible, most recent first (FR-008) | 422 if the note body is empty; 403 if `{lead}` belongs to a different agent |
| DELETE | `/leads/{lead}/notes/{note}` | `auth`, `verified`, `profile.complete`, `no-cache`, **`LeadPolicy::update`** (on `{lead}`) | Permanently delete one note from a lead | Redirects back to `/leads/{lead}/edit`; the note is gone, the rest are untouched (FR-012) | 403 if `{lead}` belongs to a different agent; 404 if `{note}` doesn't belong to `{lead}` |

## Note on `LeadPolicy` (FR-009)

Registered for the `Lead` model (Laravel auto-discovers `App\Policies\LeadPolicy` for
`App\Models\Lead`). `edit`/`update`/the notes action call `$this->authorize('update', $lead)`, and
`destroy` calls `$this->authorize('delete', $lead)`; both policy methods return
`$lead->user_id === $user->id`. `index` doesn't need a policy check — it queries only
`auth()->user()->leads()` in the first place.

## Note on linking listings (FR-007)

The edit (and create) form uses a searchable "tag picker" (`resources/views/partials/tag-picker.blade.php`,
shared with the reverse direction on `/listings`) instead of a checkbox list — see research.md. It
calls the search endpoint below and submits the same `listing_ids[]` array shape as before. On
submit, `LeadRequest` validates that every submitted listing ID is in `auth()->user()->listings()`,
then the controller calls `$lead->listings()->sync($listingIds)` — this both adds newly picked
listings and removes previously-linked ones no longer present, in one step.

## Note on the listing search endpoint (used by the tag picker)

| Method | Path | Access | Purpose | Success | Failure |
|---|---|---|---|---|---|
| GET | `/api/listings-search?query=...` | `auth` only | Search the logged-in agent's own listings by address/city, or by exact ID | 200, JSON array of `{id, label}`, at most 10 results (may be empty) | — |

This is the counterpart to `/api/leads-search` (see `specs/004-property-listings/contracts/listing-routes.md`
for the equivalent search endpoint used on the `/listings` side to link leads by name).

## Note on notes (FR-008, FR-012)

Adding a note is its own endpoint rather than part of the main lead `update`, since notes accumulate
over time instead of being replaced — see research.md. Deleting a note is also its own endpoint;
there's still no way to *edit* a note's text in this version, only add or remove one. Deleting
checks `$this->authorize('update', $lead)` (a note has no owner of its own — it belongs to a lead,
which belongs to an agent) and separately confirms `$note->lead_id === $lead->id`, so a URL can't be
used to delete a note under the wrong one of an agent's own leads.

## Note on route middleware (FR-001 through FR-012)

All lead routes sit behind the same `auth`, `verified`, `profile.complete`, `no-cache` stack as
`/listings` (see `specs/004-property-listings/contracts/listing-routes.md`) — no new middleware
introduced by this feature.
