# Phase 0 Research: Google Login

## Decision: Use Laravel Socialite's stateful web flow

- **Decision**: Use Socialite's default *stateful* driver (`Socialite::driver('google')->redirect()` /
  `->user()`), not the *stateless* variant.
- **Rationale**: The stateful flow stores a `state` value in the session and verifies it on
  callback, protecting against forged callback requests (a CSRF-equivalent protection for OAuth).
  This is Socialite's default and requires no extra code — matches the constitution's Security by
  Default and Simplicity principles.
- **Alternatives considered**: Stateless flow (rejected — loses the CSRF-equivalent protection,
  meant for APIs/mobile clients without sessions, not a server-rendered Blade app like this one).

## Decision: Make `password` nullable for Google-only accounts

- **Decision**: Alter the `users` table's `password` column to nullable, and store `null` for a
  brand-new account created purely via Google (no matching email/password account to link to).
- **Rationale**: `null` unambiguously means "this account has no password" — self-documenting,
  and requires no special-case logic to keep it "unusable." A single `->nullable()->change()` line
  in the migration is a small, low-risk change, not a rippling schema rewrite.
- **Alternatives considered**: Storing `Hash::make(Str::random(40))` as a placeholder (rejected on
  reflection — it overloads the `password` column with a second, undocumented meaning: "a real
  password OR meaningless noise," with nothing in the schema communicating which applies to a
  given row. Less explainable than `null` for the same amount of code).

## Decision: Implement account linking as find-or-create by email

- **Decision**: On Google callback, look up a `User` by the email Google returns. If found,
  attach `google_id` to that existing user (and mark it verified if it wasn't). If not found,
  create a new, already-verified user with that `google_id`.
- **Rationale**: This directly implements the spec's resolved FR-005 (auto-link by email, since
  Google's email is pre-verified) with a single, easy-to-follow lookup — no separate "linking"
  table or flow needed.
- **Alternatives considered**: A separate `social_accounts` table supporting multiple providers
  per user (rejected — over-engineered for a feature that only needs one provider today; YAGNI).

## Decision: Test Socialite by mocking the facade, never calling real Google servers

- **Decision**: Feature tests mock `Socialite::shouldReceive('driver->user')` (Mockery, via the
  facade) to return a fake Socialite user object, rather than hitting Google's real OAuth servers.
- **Rationale**: This is Socialite's own documented approach to testing consumers of it — fast,
  reliable, no external network dependency or real Google test account needed.
- **Alternatives considered**: End-to-end testing against a real (test) Google account (rejected —
  slow, flaky, requires managing real credentials in CI; not appropriate for a unit/feature test
  suite. Manual testing against real Google still happens once, via `quickstart.md`).

**Output**: All Technical Context items are resolved; no open questions block Phase 1.
