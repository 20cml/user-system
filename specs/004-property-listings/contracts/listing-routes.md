# Contracts: HTTP Routes for Property Listings

This is a server-rendered Blade app, not a JSON API — its "contract" is the set of HTTP routes it
exposes and what each one does. All routes below are new.

| Method | Path | Access | Purpose | Success | Failure |
|---|---|---|---|---|---|
| GET | `/listings` | `auth`, `verified`, `profile.complete` | List the logged-in agent's own listings | 200, only listings where `user_id` matches the logged-in agent | — |
| GET | `/listings/create` | `auth`, `verified`, `profile.complete` | Show the "add a listing" form | 200, blank form | — |
| POST | `/listings` | `auth`, `verified`, `profile.complete` | Create a new listing owned by the logged-in agent | Redirects to `/listings` with the new listing visible, status defaulted to `available` (FR-004) | 422 + validation errors for any missing required field or a price ≤ 0 (FR-002, FR-003) |
| GET | `/listings/{listing}/edit` | `auth`, `verified`, `profile.complete`, **`ListingPolicy::update`** | Show the edit form for one listing, pre-filled, including its current status | 200, only if `{listing}` belongs to the logged-in agent | 403 if `{listing}` belongs to a different agent (FR-008) |
| PATCH | `/listings/{listing}` | `auth`, `verified`, `profile.complete`, **`ListingPolicy::update`** | Save changes to a listing's details and/or status | Redirects to `/listings` with the updated values, including any status change (FR-006, FR-007) | 422 + validation errors; 403 if `{listing}` belongs to a different agent (FR-008) |

## Note on `ListingPolicy` (FR-006, FR-008)

Registered for the `Listing` model (Laravel auto-discovers `App\Policies\ListingPolicy` for
`App\Models\Listing`). Both `edit` and `update` call `$this->authorize('update', $listing)` before
doing anything else; the policy method returns `$listing->user_id === $user->id`. `index` doesn't
need a policy check — it queries only `auth()->user()->listings()` in the first place, so there's
nothing to load that could belong to someone else.

## Note on route middleware (FR-001 through FR-009)

All listing routes sit behind the same `auth`, `verified`, `profile.complete` stack as `/dashboard`
(see `specs/003-profile-completion/contracts/profile-routes.md`) — an agent who hasn't finished
onboarding shouldn't be managing listings yet. No new middleware is introduced by this feature.
