# Contracts: HTTP Routes for Mandatory Profile Completion

This is a server-rendered Blade app, not a JSON API — its "contract" is the set of HTTP routes it
exposes and what each one does. The `/profile` routes below already exist (from Laravel Breeze's
scaffold); this feature extends their behavior and adds the middleware + suggestion proxy route.

| Method | Path | Access | Purpose | Success | Failure |
|---|---|---|---|---|---|
| GET | `/dashboard` (and other protected routes) | `auth`, `verified`, **`profile.complete`** (new) | Reach the dashboard | 200, dashboard — only if `profile_completed_at` is set | Redirects to `/profile` if the profile isn't complete yet |
| GET | `/profile` | `auth` only (no `profile.complete`) | Show the profile form | 200, form pre-filled with any existing values | — |
| PATCH | `/profile` | `auth` only | Save profile fields; sets `profile_completed_at` once every required field is present | Redirects to `/profile` with a "saved" status; if this was the first time all required fields were present, `profile_completed_at` is set and the next dashboard visit succeeds | 422 + validation errors for any missing required field (FR-004) |
| GET | `/api/address-suggestions?query=...` | `auth` only | Server-side proxy to the third-party address-lookup API | 200, JSON array of suggestions (may be empty) | 200 with an empty array if the third-party service errors or times out — the frontend must treat "no suggestions" as a normal, non-blocking outcome (FR-007), never a hard failure |

## Note on the new `profile.complete` middleware (FR-001, FR-002, FR-008)

Registered as a named middleware alias (in `bootstrap/app.php`, next to where `auth`/`guest`/
`verified` are already aliased) and attached to the dashboard route's existing middleware list,
alongside `auth` and `verified` — not applied to `/profile` itself, or a user could never reach
the form that lets them become complete in the first place. It checks
`$request->user()->profile_completed_at !== null`; if false, it redirects to `route('profile.edit')`
instead of letting the request continue. This applies identically no matter which controller
authenticated the user (email/password, Google, or the email-verification link), since none of
those redirect targets change — only what the middleware allows through afterward changes,
exactly like `verified` already works today (see `specs/001-email-password-auth/contracts/auth-routes.md`).

## Note on the address-suggestion proxy (FR-005, FR-006, FR-007)

The frontend never calls the third-party API directly — it calls this first-party endpoint, which
forwards the query server-side (keeping the API key out of the browser; see research.md) and
returns a normalized shape the Blade view's JS can render as a dropdown:

```json
[
  { "street": "123 Main St", "city": "Toronto", "state_province": "ON", "postal_code": "M5V 2T6", "country": "CA" }
]
```

Selecting a suggestion fills `address_line`, `city`, `state_province`, `postal_code`, and `country` in the
form; the user may still edit any of them before submitting (FR-006).
