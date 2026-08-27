# Quickstart: Validate Email & Password Authentication

## Prerequisites

- MySQL running locally and `.env` configured with `DB_CONNECTION=mysql` and matching credentials
  (still pending as of this plan — tracked as a setup task before implementation).
- `.env` `MAIL_MAILER=log` for local development, so verification emails are written to
  `storage/logs/laravel.log` instead of requiring a real mail server.
- Dependencies installed: `composer install`, `npm install`.

## Setup

```bash
php artisan migrate
npm run build
php artisan serve
```

## Automated validation

```bash
php artisan test --filter=Auth
```

Expected: all tests in `tests/Feature/Auth/` (RegistrationTest, AuthenticationTest,
EmailVerificationTest) pass.

## Manual validation walkthrough

1. Visit `/register`, submit a new email + password (8+ characters).
   - **Expected**: redirected to `/verify-email` with a "check your email" message.
2. Open `storage/logs/laravel.log`, find the most recent mail entry, copy the verification URL from it.
   - **Expected**: a signed URL like `http://localhost:8000/verify-email/1/<hash>?expires=...&signature=...`.
3. Try visiting `/login` in a separate private/incognito window and logging in with the same
   credentials before visiting the verification link.
   - **Expected**: login succeeds (session starts), but the user lands on `/verify-email` and
     cannot reach any other page — see the note in `contracts/auth-routes.md` on why this
     satisfies "blocked" without rejecting the login form itself.
4. Visit the verification URL copied in step 2.
   - **Expected**: redirected to the app's authenticated landing page; the account is now verified.
5. Log out, then log back in at `/login` with the same credentials.
   - **Expected**: immediate access, no verification prompt.
6. Try registering again with the same email from step 1.
   - **Expected**: validation error, "email already in use."
7. Try logging in with the correct email but a wrong password.
   - **Expected**: generic "these credentials do not match" error (doesn't say which field was wrong).
8. Repeat step 7 several times quickly.
   - **Expected**: eventually rate-limited (429 / "too many attempts" message).
