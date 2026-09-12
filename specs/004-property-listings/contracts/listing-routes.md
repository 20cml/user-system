# Contracts: HTTP Routes for Property Listings

This is a server-rendered Blade app, not a JSON API — its "contract" is the set of HTTP routes it
exposes and what each one does. All routes below are new.

| Method | Path | Access | Purpose | Success | Failure |
|---|---|---|---|---|---|
| GET | `/listings?status=&listing_type=&property_type=&min_price=&max_price=` | `auth`, `verified`, `profile.complete` | List the logged-in agent's own listings, newest first; every query parameter is optional and combinable | 200, only listings where `user_id` matches the logged-in agent, further narrowed by whichever filters are present (FR-017, FR-018) | — |
| GET | `/listings/create` | `auth`, `verified`, `profile.complete` | Show the "add a listing" form | 200, blank form | — |
| POST | `/listings` | `auth`, `verified`, `profile.complete` | Create a new listing owned by the logged-in agent, optionally with up to 3 photos | Redirects to `/listings` with the new listing visible, status defaulted to `available`, `currency` derived from the agent's own profile country (FR-004, FR-010) | 422 + validation errors for any missing required field, a price ≤ 0, or more than 3 photos (FR-002, FR-003, FR-012) |
| GET | `/listings/{listing}/edit` | `auth`, `verified`, `profile.complete`, **`ListingPolicy::update`** | Show the edit form for one listing, pre-filled, including its current status and existing photos (each removable, each with a position field) | 200, only if `{listing}` belongs to the logged-in agent | 403 if `{listing}` belongs to a different agent (FR-008) |
| PATCH | `/listings/{listing}` | `auth`, `verified`, `profile.complete`, **`ListingPolicy::update`** | Save changes to a listing's details and/or status; add new photos, remove marked ones, and/or reorder existing ones | Redirects to `/listings` with the updated values, including any status change and photo changes (FR-006, FR-007, FR-011, FR-013, FR-014) | 422 + validation errors; 403 if `{listing}` belongs to a different agent (FR-008) |
| GET | `/api/address-suggestions?query=...` | `auth` only | Reused as-is from Feature 3 — server-side proxy to the address-lookup API (see `specs/003-profile-completion/contracts/profile-routes.md`) | 200, JSON array of suggestions (may be empty) | 200 with an empty array on any third-party failure |

## Note on `ListingPolicy` (FR-006, FR-008)

Registered for the `Listing` model (Laravel auto-discovers `App\Policies\ListingPolicy` for
`App\Models\Listing`). Both `edit` and `update` call `$this->authorize('update', $listing)` before
doing anything else; the policy method returns `$listing->user_id === $user->id`. `index` doesn't
need a policy check — it queries only `auth()->user()->listings()` in the first place, so there's
nothing to load that could belong to someone else.

## Note on `country` vs `currency` (FR-010, FR-015)

`country` **is** a regular field in the create/edit form, part of the address, filled in by the
same autocomplete as the profile page — it describes the property, not the agent. `currency` is
the opposite: it's never in the request payload at all — the controller sets it from
`auth()->user()->country` (the *agent's* profile), independent of whatever country the listing's
own address turns out to be.

## Note on route middleware (FR-001 through FR-018)

All listing routes sit behind the same `auth`, `verified`, `profile.complete` stack as `/dashboard`
(see `specs/003-profile-completion/contracts/profile-routes.md`) — an agent who hasn't finished
onboarding shouldn't be managing listings yet. No new middleware is introduced by this feature.

## Note on photos (FR-011 through FR-014)

Photos are submitted through the same `store`/`update` requests as the rest of a listing's fields
(multipart form), not a separate endpoint: a `photos[]` array of uploaded files (new photos, up to
the remaining slots under the 3-photo cap), a `remove_photos[]` array of existing `ListingPhoto`
IDs to delete, and a `photo_order[{id}]` mapping of existing photo IDs to their new position. All
three are optional on any given submission.

## Note on the address-suggestion endpoint (FR-001, FR-002)

The listing form's postal/zip code field uses the exact same `/api/address-suggestions` endpoint
and frontend JS pattern as the profile page — no changes to that controller, since it already
covers both Canada and the United States, matching Listings' own country options.
