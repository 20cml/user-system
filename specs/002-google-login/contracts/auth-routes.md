# Contracts: HTTP Routes for Google Login

Two new routes, added to the same guest-accessible group as `/login` and `/register`
(see Feature 1's `contracts/auth-routes.md` for the existing routes).

| Method | Path | Access | Purpose | Success | Failure |
|---|---|---|---|---|---|
| GET | `/auth/google/redirect` | guest only | Send the visitor to Google's OAuth consent screen | 302 redirect to Google | — |
| GET | `/auth/google/callback` | guest only | Google redirects back here after consent | Finds-or-creates/links the `User` (see data-model.md), starts an authenticated session, redirects to `/dashboard` | Redirects to `/login` with a flash error if Google reports the email as unverified, or if the visitor cancelled/denied consent |

## Note on account linking (FR-005)

On a successful callback, the email Google returns is looked up against existing `User` records.
A match links `google_id` onto that user (and verifies it, if unverified); no match creates a new,
already-verified user. Either way, the outcome to the visitor is identical: an authenticated
session and a redirect to `/dashboard` — the linking-vs-creating distinction is invisible in the
HTTP response, it only affects which database row ends up associated with the session.
