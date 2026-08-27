# Contracts: HTTP Routes for Email & Password Authentication

This is a server-rendered Blade app, not a JSON API — its "contract" is the set of HTTP routes it
exposes and what each one does.

| Method | Path | Access | Purpose | Success | Failure |
|---|---|---|---|---|---|
| GET | `/register` | guest only | Show registration form | 200, form | — |
| POST | `/register` | guest only | Create account | Creates user, fires `Registered` event (sends verification email), starts session, redirects to `/verify-email`. If the email matches an existing **unverified** account, no new account is created — a fresh verification email is resent to it instead, same redirect. | 422 + validation errors (email already verified by another account, weak password) |
| GET | `/verify-email` | authenticated, unverified | Show "check your email" prompt | 200, prompt page | — |
| POST | `/email/verification-notification` | authenticated, unverified | Resend verification email | Redirects back with "link sent" status | Rate-limited (429) if requested too often |
| GET | `/verify-email/{id}/{hash}` | authenticated, signed URL | Verify the email via the emailed link | Marks `email_verified_at`, fires `Verified` event, redirects to intended page | 403 if the signed URL is invalid/expired |
| GET | `/login` | guest only | Show login form | 200, form | — |
| POST | `/login` | guest only | Authenticate | Starts session, redirects to intended page | 422 generic "these credentials do not match" (doesn't reveal which field was wrong, satisfies FR-006); 429 if rate-limited (FR-008) |

## Note on "blocking" an unverified account (FR-009)

The spec's acceptance scenario says an unverified account attempting to log in should be
"blocked ... with an explanatory message." The idiomatic Laravel/Breeze implementation of this is
not to reject the `/login` form itself, but to let the session authenticate normally and then use
the built-in `verified` middleware to gate every other page behind the `/verify-email` prompt —
so an unverified user who logs in immediately lands on "please check your email" and can reach
nothing else, which satisfies the intent (no real access without verification) using Laravel's
standard mechanism rather than custom logic bolted onto the login form.
