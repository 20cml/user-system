# Phase 1 Data Model: Google Login

## Entity: User (extended)

This feature adds one column to the `User` entity already defined in Feature 1
(`specs/001-email-password-auth/data-model.md`):

| Field | Type | Rules |
|---|---|---|
| `google_id` | string, nullable | The Google account's unique subject identifier. `null` for accounts that have never linked Google. **Unique** when present (one Google identity maps to at most one `User`). |

This feature also changes one existing field: `password` becomes **nullable** (it was `NOT NULL`
in Feature 1). For an account created purely via Google (no linked email/password account),
`password` is `null` — unambiguously "this account has no password" (see research.md). Setting a
real password for such an account later is out of scope for this feature.

### State: how an account acquires a `google_id`

```text
[No account for this email] --(Google sign-in)--> [New User, google_id set, verified]

[Existing User, no google_id] --(Google sign-in, matching email)--> [Same User, google_id set, verified]
```

There is no path that removes a `google_id` once set (unlinking Google is out of scope for this
feature).

### Validation rules (from spec.md Functional Requirements)

- `google_id`, when present, is unique across all users (FR-004 — no duplicate accounts for one
  Google identity).
- Linking (FR-005) only happens by matching the account's `email` to the email Google reports for
  that identity — no other matching strategy is used.
