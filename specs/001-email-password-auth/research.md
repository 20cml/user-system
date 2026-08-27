# Phase 0 Research: Email & Password Authentication

No `[NEEDS CLARIFICATION]` markers remained in the Technical Context after drafting it against
the project constitution and spec — the constitution already fixed the stack (Laravel + MySQL +
Breeze + Socialite). The decisions below document the reasoning behind the choices made within
that stack, so they're understood rather than just assumed.

## Decision: Use Laravel Breeze for auth scaffolding

- **Decision**: Laravel Breeze generates the registration/login controllers, routes, and Blade views.
- **Rationale**: The constitution already commits to Breeze (Principle I — simplicity/convention).
  Breeze ships minimal, readable code the author can read top-to-bottom, unlike Fortify (headless,
  requires building all views yourself) or Jetstream (adds teams/API tokens — more than this
  project needs).
- **Alternatives considered**: Laravel Fortify (rejected — no default views, more setup for the
  same result); building auth from scratch (rejected — reinvents well-tested, security-sensitive
  code for no learning benefit beyond what Breeze's readable source already teaches).

## Decision: Use Laravel's built-in email verification (signed URLs)

- **Decision**: Implement `MustVerifyEmail` on the `User` model and use Laravel's built-in signed
  route + `VerifyEmail` notification, rather than a custom verification-token database table.
- **Rationale**: Laravel's signed URLs are cryptographically verified and time-limited
  out of the box (satisfies FR-010/FR-012 — time-limited, single-use-in-practice links) without
  storing or managing tokens ourselves. This is the framework-idiomatic approach Breeze itself
  uses when email verification is enabled.
- **Alternatives considered**: A custom `verification_tokens` table with manually generated/expired
  tokens (rejected — duplicates functionality Laravel already provides securely, adds a table and
  code to maintain for no added benefit).

## Decision: Test against in-memory SQLite, run the app on MySQL

- **Decision**: Feature tests use Laravel's `RefreshDatabase` trait against an in-memory SQLite
  connection configured for the test environment; the application itself runs on MySQL per the
  constitution.
- **Rationale**: This is a standard, widely-used Laravel testing pattern — in-memory SQLite makes
  the test suite fast and avoids requiring a running MySQL server just to run tests, while the
  actual dev/prod database stays MySQL as required. Laravel's migrations and Eloquent queries used
  in this feature are portable between the two.
- **Alternatives considered**: Running tests directly against MySQL (rejected for this project —
  slower, and requires a MySQL service to be running any time tests run, including in CI later).

## Decision: Use Laravel's built-in login throttling

- **Decision**: Use Laravel's built-in `RateLimiter` (as wired up by Breeze's login controller) to
  satisfy FR-008 (rate-limit failed login attempts), rather than custom lockout logic.
- **Rationale**: It's already part of the Breeze scaffolding, tested, and configurable — no reason
  to write custom brute-force protection.
- **Alternatives considered**: Custom failed-attempt counter on the `users` table (rejected —
  more code, no advantage over the built-in limiter).

## Decision: Resend verification instead of blocking on unverified duplicate email

- **Decision**: Registering with an email that already belongs to an *unverified* account resends
  a fresh verification link rather than rejecting the attempt as "already in use." Only *verified*
  accounts block re-registration.
- **Rationale**: Without this, a typo'd or maliciously-entered registration would permanently lock
  the real owner of that email out of ever registering (Security by Default principle — an
  attacker could "squat" any email address just by attempting registration with it and never
  verifying).
- **Alternatives considered**: Expiring/purging unverified accounts after a time window (rejected
  for now — adds a scheduled cleanup job for a benefit already covered by the resend approach;
  could be revisited later if abandoned unverified accounts become a real problem).

**Output**: All Technical Context items are resolved; no open questions block Phase 1.
