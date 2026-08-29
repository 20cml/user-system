# Quickstart: Validate Google Login

## Prerequisites

- A Google Cloud project with an OAuth 2.0 Client ID configured (Web application type), with
  `http://localhost:8000/auth/google/callback` registered as an authorized redirect URI.
- `.env` populated with `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, and
  `GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/callback` (still pending as of this
  plan — tracked as a setup task before implementation, same as Feature 1's MySQL setup was).
- Feature 1 (email/password authentication) already implemented — this feature depends on it.

## Setup

```bash
php artisan migrate
```

(Applies the new `google_id` column migration.)

## Automated validation

```bash
php artisan test --filter=GoogleAuthenticationTest
```

Expected: all tests pass, using a mocked Socialite response (no real Google account needed for
this part).

## Manual validation walkthrough

1. Visit `/login`, click "Continue with Google."
   - **Expected**: redirected to Google's real consent screen.
2. Approve access with a real Google account whose email has never been used in this app.
   - **Expected**: redirected back to `/dashboard`, logged in. Check the database — a new `User`
     row exists with that email, `email_verified_at` already set, and `google_id` populated.
3. Log out, then repeat steps 1–2 with the same Google account.
   - **Expected**: logged into the same account (same user id) — not a second row created.
4. Register a new account manually via `/register` with a different, fresh email/password.
5. Log out, then click "Continue with Google" using a Google account whose email matches the one
   from step 4.
   - **Expected**: logged into the account from step 4 (same user id), which is now also verified
     if it wasn't already, and `google_id` is now populated on it.
