# Phase 1 Data Model: Email & Password Authentication

## Entity: User

Represents a registered person (from spec.md's Key Entities section).

| Field | Type | Rules |
|---|---|---|
| `id` | bigint, primary key | auto-increment |
| `first_name` | string | required, max 255 chars |
| `email` | string | required, valid email format, **unique** across all users |
| `email_verified_at` | timestamp, nullable | `null` until the user verifies their email; set to the verification time on success |
| `password` | string | required at registration, min 8 characters, stored as a bcrypt hash — never plain text |
| `remember_token` | string, nullable | Laravel's standard "remember me" session token (framework-managed) |
| `created_at` / `updated_at` | timestamp | standard Eloquent timestamps |

This matches the columns in the default migration
(`database/migrations/0001_01_01_000000_create_users_table.php`, plus
`database/migrations/2026_08_31_221836_rename_name_to_first_name_on_users_table.php` for
`first_name`). The registration form's visible label is "Name"; the underlying field is
`first_name`, paired with the `last_name` field Feature 003 adds to the profile page.

### State: email verification status

A user is in exactly one of two states, derived from `email_verified_at`:

```text
[Registered, unverified] --(clicks valid verification link)--> [Verified]
```

- **Unverified** (`email_verified_at IS NULL`): account exists, but login is blocked (FR-009).
- **Verified** (`email_verified_at` set): login is allowed (User Story 3).

There is no path back from Verified to Unverified in this feature — that's out of scope.

### Validation rules (from spec.md Functional Requirements)

- Email: required, valid format, unique (FR-002)
- Password: required, minimum 8 characters (FR-003)
- Password is never persisted or logged unhashed (FR-004)

No other entities are introduced by this feature — verification links are Laravel signed URLs
(see research.md), not a stored entity.
