# Contracts: HTTP Routes for Property Listings

This is a server-rendered Blade app, not a JSON API — its "contract" is the set of HTTP routes it
exposes and what each one does. All routes below are new.

| Method | Path | Access | Purpose | Success | Failure |
|---|---|---|---|---|---|
| GET | `/listings?status=&listing_type=&property_type=&min_price=&max_price=` | `auth`, `verified`, `profile.complete` | List the logged-in agent's own listings, newest first; every query parameter is optional and combinable | 200, only listings where `user_id` matches the logged-in agent, further narrowed by whichever filters are present (FR-017, FR-018) | — |
| GET | `/listings/create` | `auth`, `verified`, `profile.complete` | Show the "add a listing" form | 200, blank form | — |
| POST | `/listings` | `auth`, `verified`, `profile.complete` | Create a new listing owned by the logged-in agent, optionally with one photo | Redirects to `/listings` with the new listing visible, status defaulted to `available`, `currency` derived from the agent's own profile country (FR-004, FR-010) | 422 + validation errors for any missing required field or a price ≤ 0 (FR-002, FR-003) |
| GET | `/listings/{listing}/edit` | `auth`, `verified`, `profile.complete`, **`ListingPolicy::update`** | Show the edit form for one listing, pre-filled, including its current status and photo (if any, removable) | 200, only if `{listing}` belongs to the logged-in agent | 403 if `{listing}` belongs to a different agent (FR-008) |
| PATCH | `/listings/{listing}` | `auth`, `verified`, `profile.complete`, **`ListingPolicy::update`** | Save changes to a listing's details and/or status; replace or remove its photo | Redirects to `/listings` with the updated values, including any status change and photo change (FR-006, FR-007, FR-011, FR-012, FR-013) | 422 + validation errors; 403 if `{listing}` belongs to a different agent (FR-008) |
| DELETE | `/listings/{listing}` | `auth`, `verified`, `profile.complete`, **`ListingPolicy::delete`** | Permanently delete a listing (and its photo file, if any) | Redirects to `/listings`; the listing is gone (FR-019) | 403 if `{listing}` belongs to a different agent (FR-008) |
| GET | `/api/address-suggestions?query=...` | `auth` only | Reused as-is from Feature 3 — server-side proxy to the address-lookup API (see `specs/003-profile-completion/contracts/profile-routes.md`) | 200, JSON array of suggestions (may be empty) | 200 with an empty array on any third-party failure |

## Note on `ListingPolicy` (FR-006, FR-008, FR-019)

Registered for the `Listing` model (Laravel auto-discovers `App\Policies\ListingPolicy` for
`App\Models\Listing`). `edit`/`update` call `$this->authorize('update', $listing)` and `destroy`
calls `$this->authorize('delete', $listing)` before doing anything else; both policy methods return
`$listing->user_id === $user->id`. `index` doesn't need a policy check — it queries only
`auth()->user()->listings()` in the first place, so there's nothing to load that could belong to
someone else.

## Note on `country` vs `currency` (FR-010, FR-015)

`country` **is** a regular field in the create/edit form, part of the address, filled in by the
same autocomplete as the profile page — it describes the property, not the agent. `currency` is
the opposite: it's never in the request payload at all — the controller sets it from
`auth()->user()->country` (the *agent's* profile), independent of whatever country the listing's
own address turns out to be.

## Note on deletion vs. status (FR-009, FR-019)

`DELETE /listings/{listing}` is a hard delete, distinct from setting `status` to `closed` via
`PATCH` — closing keeps the row as history; deleting removes it and its photo file permanently. The
edit page's delete button asks for confirmation (a plain JS `confirm()`) before submitting, since
there's no undo.

## Note on route middleware (FR-001 through FR-019)

All listing routes sit behind the same `auth`, `verified`, `profile.complete` stack as `/dashboard`
(see `specs/003-profile-completion/contracts/profile-routes.md`), plus a `no-cache` middleware
(`PreventBrowserCaching`) shared with `/dashboard` and `/profile` — see the note below.

## Note on the `no-cache` middleware

`/dashboard`, `/profile`, and `/listings` all show data that changes on every request, so each is
also gated by a `no-cache` middleware alias (`App\Http\Middleware\PreventBrowserCaching`) that sets
`Cache-Control: no-store, no-cache, must-revalidate` on the response. Without it, some browsers'
back/forward cache serves a stale copy of the page instead of asking the server again after a
change (e.g., a listing's new photo or status not showing up until a hard refresh).

## Note on the photo (FR-011, FR-012, FR-013)

The photo is submitted through the same `store`/`update` requests as the rest of a listing's fields
(multipart form), not a separate endpoint: an optional `photo` file field (uploading one when a
listing already has a photo replaces it), and an optional `remove_photo` checkbox to delete it
without replacing it.

## Note on the address-suggestion endpoint (FR-001, FR-002)

The listing form's postal/zip code field uses the exact same `/api/address-suggestions` endpoint
and frontend JS pattern as the profile page — no changes to that controller, since it already
covers both Canada and the United States, matching Listings' own country options.
