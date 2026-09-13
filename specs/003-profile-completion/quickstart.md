# Quickstart: Validate Mandatory Profile Completion

## Prerequisites

- MySQL running locally, migrations up to date (`php artisan migrate` includes this feature's new
  migration once implemented).
- Address-lookup API key set in `.env` (e.g. `GEOAPIFY_API_KEY=...`) and read via
  `config/services.php`. Without it, the suggestion endpoint should still return an empty list
  gracefully rather than error (FR-007) — worth testing with the key unset too.
- Features 001 and 002 already working (registration, email verification, login, Google sign-in) —
  this feature builds on top of their existing redirect-to-dashboard behavior without changing it.

## Setup

```bash
php artisan migrate
npm run build
php artisan serve
```

## Automated validation

```bash
php artisan test --filter=ProfileCompletion
```

Expected: all tests in `tests/Feature/ProfileCompletionTest.php` pass, including the ones that use
`Http::fake()` to simulate the address-lookup API (no real network call made during the suite).

## Manual validation walkthrough

1. Register a brand-new account via `/register` and verify it via the emailed link (see
   `specs/001-email-password-auth/quickstart.md` steps 1–4 if this flow is unfamiliar).
   - **Expected**: instead of the dashboard, the browser lands on `/profile` with the form empty.
2. Try navigating directly to `/dashboard` while the profile is still incomplete.
   - **Expected**: redirected straight back to `/profile`.
3. In the address field, type a real Canadian postal code (e.g. `M5V 2T6`) or U.S. zip code
   (e.g. `90210`).
   - **Expected**: a matching suggestion appears; selecting it fills street, city, state/province,
     postal code, and country automatically, and those fields remain editable afterward.
4. Leave one required field empty (e.g. last name) and submit.
   - **Expected**: validation error naming the missing field(s); still on `/profile`.
5. Fill in every required field (phone, address, city, state/province, postal code, country, last
   name — complement may stay blank) and submit.
   - **Expected**: redirected to `/dashboard` successfully.
6. Log out, then log back in with the same account.
   - **Expected**: goes straight to `/dashboard` — no detour through `/profile`.
7. Visit `/profile` voluntarily after step 6 and change one field, then save.
   - **Expected**: saves normally; `/dashboard` remains reachable afterward (the mandatory redirect
     does not re-trigger).
8. Repeat steps 1–2 using a brand-new Google sign-up instead of email/password.
   - **Expected**: identical behavior — same `/profile` detour before the first dashboard visit.
