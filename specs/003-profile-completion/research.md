# Phase 0 Research: Mandatory Profile Completion

## Decision: Address-lookup provider — Geoapify Address Autocomplete

**Rationale**: Free tier of 3,000 requests/day with no credit card required to sign up, street-
level suggestions (not just city/province from the postal code prefix), and confirmed coverage of
both Canada and the United States. Fits this project's scale with margin to spare, and matches
spec FR-005/FR-006's requirement for real address suggestions, not just city/state lookup.

**Alternatives considered**:
- **Zippopotam.us** — genuinely free, no API key at all, but only maps a postal/zip code to
  city + province/state (no street-level data). Rejected because the spec explicitly wants the
  suggestion to fill in the rest of the address, not just the city.
- **Google Places Autocomplete** — very accurate and widely used, but requires a Google Cloud
  billing account (credit card on file) even though usage would stay within the free monthly
  credit. Rejected to avoid adding a billing dependency to a learning project.
- **Canada Post AddressComplete** — the most accurate option for Canadian addresses specifically,
  but it's a paid product with no meaningful free tier, and doesn't cover the U.S. Rejected on cost
  and scope grounds.

## Decision: Selecting a suggestion never auto-fills `address_line`

**Rationale**: A postal/zip code alone identifies a small area, not one specific street — querying
Geoapify with just a postal code (no house number or street name) returns a `postcode`-type result
with no `street`/`housenumber` data at all; its `street` value is really just the postcode's own
formatted description (e.g., "Toronto, ON M4Y 2P7"), not a real address. Auto-filling `address_line`
with that text would silently put wrong data in the field. City, state/province, and country *are*
reliably known at the postcode level, so those still get filled; the street stays for the user to
type by hand (the dropdown still shows Geoapify's `street` value as part of each suggestion's label,
which is genuinely useful when the user has typed enough of a real address for it to be accurate —
just not applied to the field automatically).

**Alternatives considered**:
- **Auto-filling `address_line` whenever Geoapify returns a non-empty `street` value** — the
  original design. Confirmed via a direct API call that a bare postal code's `street` value is the
  postcode's own description, not a real street, so this was filling the field with misleading data.

## Decision: Enforce the gate via a route middleware, not controller edits

**Rationale**: Spec FR requires that `RegisteredUserController`, `AuthenticatedSessionController`,
`GoogleAuthController`, and `VerifyEmailController` stay untouched. All four already redirect to
`route('dashboard')` without knowing anything about verification status today — the existing
`verified` middleware is what actually enforces that gate, sitting on the route definition in
`routes/web.php`, not inside any of those controllers. A new `EnsureProfileIsComplete` middleware,
added to the same route(s) alongside `auth` and `verified`, follows the exact same pattern and
requires zero changes to those four files.

**Alternatives considered**:
- **Editing each controller's redirect target** — would work, but duplicates the same
  "is this user allowed through?" check in three or four places instead of one, and directly
  contradicts the spec's requirement to leave those controllers alone.
- **A global `after` hook on the `Authenticated` event** — possible, but less idiomatic in Laravel
  than route middleware for gating specific routes, and harder to reason about than "this route
  requires X, Y, Z middleware," which is already the established pattern in this codebase.

## Decision: Keep the third-party API key server-side via a thin proxy route

**Rationale**: Calling the address-lookup API directly from the browser would require exposing the
API key in client-side JavaScript, which risks quota abuse by anyone who reads it from the page
source. A first-party route (`Api/AddressSuggestionController.php`) receives the partial
input from the browser, calls the third-party API server-side using Laravel's `Http` facade and
a key stored in `.env`, and returns just the suggestion data the frontend needs. This satisfies
constitution Principle II (secrets never exposed) without adding any new package.

**Alternatives considered**:
- **Direct client-side calls to the third-party API** — simpler to build, but exposes the API key
  in the page's JavaScript. Rejected on security grounds.

## Decision: Mock the third-party API in tests with `Http::fake()`

**Rationale**: Laravel's built-in `Http::fake()` lets Feature tests assert on outgoing requests and
return canned responses, without any real network call. This keeps the test suite fast,
deterministic, and independent of the third-party service's uptime or daily quota — consistent
with how 001/002 avoid external dependencies in their test suites (e.g., `MAIL_MAILER=log`
locally instead of sending real email).

**Alternatives considered**:
- **Hitting the real API in tests** — would make the suite flaky (network-dependent) and could
  burn through the free daily quota during CI runs. Rejected.
