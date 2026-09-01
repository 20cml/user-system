# Phase 1 Data Model: Mandatory Profile Completion

## Entity: User (extended)

Extends the existing `User` entity (see `specs/001-email-password-auth/data-model.md`) with the
new profile attributes named in spec.md's Key Entities section. Added via
`..._add_profile_fields_to_users_table.php` and
`..._add_last_name_and_drop_company_name_from_users_table.php`.

| Field | Type | Rules |
|---|---|---|
| `last_name` | string, nullable | required to mark the profile complete (FR-003); paired with `first_name` (Feature 001) as two distinct fields on the profile page |
| `phone` | string, nullable | required to mark the profile complete (FR-003); nullable at the column level since it doesn't exist until then |
| `address_line` | string, nullable | street address; required to mark the profile complete (FR-003) |
| `address_complement` | string, nullable | unit/suite/apartment number — may stay empty even once the profile is otherwise complete (spec Assumptions) |
| `city` | string, nullable | required to mark the profile complete (FR-003) |
| `region` | string, nullable | state (U.S.) or province (Canada); required to mark the profile complete (FR-003) |
| `postal_code` | string, nullable | required to mark the profile complete (FR-003); the value the address-suggestion lookup is keyed on |
| `country` | string, nullable | required to mark the profile complete (FR-003); constrained to `CA` or `US` at the validation layer per spec Assumptions (address-suggestion only supports these two for now) |
| `profile_completed_at` | timestamp, nullable | `null` until every required field above (plus `first_name`, from the 001 entity) has been saved at least once; set to the completion time on success — mirrors `email_verified_at`'s existing pattern |

All new columns are nullable at the database level (a user can exist without them, exactly
like `email_verified_at` starts `null`) — "required" above describes the **application-level**
rule enforced when the profile form is submitted (FR-004), not a database `NOT NULL` constraint.

### State: profile completion status

A user is in exactly one of two states, derived from `profile_completed_at`:

```text
[Logged in, profile incomplete] --(submits all required fields)--> [Profile complete]
```

- **Incomplete** (`profile_completed_at IS NULL`): the dashboard and other protected routes are
  unreachable; only the profile page is (User Story 1).
- **Complete** (`profile_completed_at` set): the dashboard is reachable, and the profile page is
  never forced again (User Story 3) — though the user may still visit and edit it voluntarily.

There is no path back from Complete to Incomplete in this feature — editing the profile later
does not unset `profile_completed_at`, since every required field is already filled by then.

### Validation rules (from spec.md Functional Requirements)

- `last_name`, `phone`, `address_line`, `city`, `region`, `postal_code`, `country`: required
  (non-empty) before the profile can be marked complete (FR-003, FR-004)
- `address_complement`: always optional (spec Assumptions)
- `country`: one of `CA`, `US` (spec Assumptions — address-suggestion scope)

No other entities are introduced by this feature — address suggestions are transient API
responses (see contracts/), not a stored entity.
