# Feature Specification: Email & Password Authentication

**Feature Branch**: `001-email-password-auth`

**Created**: 2026-08-25

**Status**: Draft

**Input**: User description: "Users can register for an account using their email and password, and log in using those same credentials. Passwords must be securely hashed. After logging in, the user has an authenticated session until they log out."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Register for a new account (Priority: P1)

A new visitor creates an account by providing an email address and a password, so they can access the system as a recognized user.

**Why this priority**: Nothing else in the system is possible without an account existing first — this is the foundation every other feature depends on.

**Independent Test**: Can be fully tested by submitting a valid email and password on the registration form and confirming a new account is created and stored.

**Acceptance Scenarios**:

1. **Given** no account exists for an email address, **When** the visitor submits that email with a valid password, **Then** a new account is created and the password is stored in hashed form (never as plain text).
2. **Given** an account already exists for an email address, **When** a visitor tries to register with that same email, **Then** registration is rejected with a clear message that the email is already in use.
3. **Given** a visitor submits a password that doesn't meet the minimum strength requirement, **When** they submit the registration form, **Then** registration is rejected with a clear explanation of the requirement.

---

### User Story 2 - Verify email address (Priority: P1)

After registering, a user confirms they own the email address they registered with, by clicking a confirmation link sent to that address.

**Why this priority**: Login (User Story 3) is gated on this — no one can use their account until this step is complete, so it's as foundational as registration itself.

**Independent Test**: Can be fully tested by registering an account, retrieving the verification link sent to that email, and confirming that clicking it marks the account as verified.

**Acceptance Scenarios**:

1. **Given** a newly registered, unverified account, **When** the user clicks the valid verification link sent to their email, **Then** the account is marked verified and the user can now log in.
2. **Given** an unverified account, **When** the user attempts to log in before verifying, **Then** the system blocks login and explains that the email must be verified first.
3. **Given** a user who didn't receive the email or whose link expired, **When** they request a new verification email, **Then** a fresh verification link is sent.
4. **Given** an expired or already-used verification link, **When** the user clicks it, **Then** the system explains the link is no longer valid and offers to resend a new one.

---

### User Story 3 - Log in with a verified account (Priority: P2)

A registered user enters their email and password to access their account.

**Why this priority**: This is the recurring action that makes the account created in User Story 1 useful — without it, registration has no ongoing value.

**Independent Test**: Can be fully tested by registering an account, then submitting its correct email/password on the login form and confirming an authenticated session starts.

**Acceptance Scenarios**:

1. **Given** a verified account, **When** the user submits the correct email and password, **Then** they are logged in and an authenticated session begins.
2. **Given** a verified account, **When** the user submits an incorrect password, **Then** login is rejected with a generic error that does not reveal whether the email or the password was wrong.
3. **Given** no account exists for an email, **When** someone attempts to log in with that email, **Then** login is rejected with the same generic error as an incorrect password (no indication the account doesn't exist).
4. **Given** a user is logged in, **When** they reload the page or navigate elsewhere in the app, **Then** they remain authenticated without re-entering credentials.

---

### Edge Cases

- What happens when someone submits the registration form with an email in an invalid format (e.g., missing "@")?
- What happens when someone repeatedly submits wrong passwords for the same account in a short period?
- What happens when the password field is submitted empty, or as whitespace only?
- What happens when a user's session expires while they're mid-action (e.g., submitting a form)?
- What happens when someone clicks a verification link a second time, after already verifying?
- What happens when someone requests a resend of the verification email repeatedly in a short period?

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: System MUST allow a visitor to create a new account by providing an email address and a password.
- **FR-002**: System MUST validate that the submitted email is in a valid format and is not already registered to another account.
- **FR-003**: System MUST enforce a minimum password strength (at least 8 characters) at registration.
- **FR-004**: System MUST store passwords using a secure, one-way hash; passwords MUST NEVER be stored or logged in plain text.
- **FR-005**: System MUST allow a registered user to log in by providing their email and password.
- **FR-006**: System MUST reject login attempts with an incorrect email/password combination, using a generic error that does not reveal whether the email exists or the password was wrong.
- **FR-007**: System MUST establish an authenticated session upon successful login that persists across page requests until the user logs out or the session expires.
- **FR-008**: System MUST rate-limit repeated failed login attempts against the same account to reduce brute-force guessing risk.
- **FR-009**: System MUST require a user to verify their email address before they can log in; login attempts on an unverified account MUST be blocked with an explanatory message.
- **FR-010**: System MUST send a verification email containing a unique, time-limited confirmation link when a new account is created.
- **FR-011**: System MUST allow a user to request the verification email be resent if it wasn't received or the link expired, and MUST rate-limit resend requests to prevent abuse.
- **FR-012**: System MUST invalidate a verification link after it has been used once or after it expires.

### Key Entities *(include if feature involves data)*

- **User**: A registered person. Key attributes: unique email address, securely hashed password, display name, account creation timestamp, email verification status/timestamp.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A new user can complete registration in under 1 minute.
- **SC-002**: A returning user can log in in under 15 seconds.
- **SC-003**: 100% of stored passwords are irreversibly hashed — a database read alone never exposes a usable plain-text password.
- **SC-004**: 100% of login attempts with incorrect credentials are rejected; no incorrect-credential attempt ever results in access.
- **SC-005**: A newly registered user receives their verification email within 1 minute under normal conditions and can complete verification with a single click.

## Assumptions

- Password reset ("forgot password") is out of scope for this feature and will be specified separately if needed.
- Logout is specified as its own separate feature; this spec only establishes that an authenticated session exists after login.
- Google-based login is specified separately (a different feature); this feature covers only email/password.
- Standard cookie-based web session behavior is assumed; no "remember me" long-lived token requirement was specified.
- Actual email delivery time depends on the configured mail service and is outside this system's direct control.
