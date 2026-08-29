# Feature Specification: Google Login

**Feature Branch**: `002-google-login`

**Created**: 2026-08-28

**Status**: Draft

**Input**: User description: "Users can register and log in using their Google account instead of email/password. If someone already has an account with that Google account's email, they log into that same account. Google-authenticated accounts don't need separate email verification, since Google already confirmed the email."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Register with Google for the first time (Priority: P1)

A new visitor clicks "Continue with Google," authorizes the app on Google's consent screen, and is registered instantly — no separate email/password step, no verification email required, since Google already confirmed the email address.

**Why this priority**: This is the entire point of the feature — a faster, password-free path to a usable account.

**Independent Test**: Can be fully tested by completing the Google consent flow with an email not already in the system and confirming a new, already-verified account is created and the visitor is logged in.

**Acceptance Scenarios**:

1. **Given** no account exists for the email Google provides, **When** the visitor completes the Google consent flow, **Then** a new account is created, marked verified immediately (no confirmation email needed), and the visitor is logged in.
2. **Given** the visitor denies permission or cancels on Google's consent screen, **When** they return to the app, **Then** no account is created and they see a clear message that Google sign-in was cancelled.

---

### User Story 2 - Log in again with Google (Priority: P1)

A visitor who previously registered via Google clicks "Continue with Google" again and is recognized as the same returning user.

**Why this priority**: Without this, Google sign-in would only ever create new accounts, never recognize returning users — the feature wouldn't function as login.

**Independent Test**: Can be fully tested by completing the Google consent flow twice with the same Google account and confirming both times land on the same account, not two separate ones.

**Acceptance Scenarios**:

1. **Given** an account previously created via Google, **When** the same person completes the Google consent flow again, **Then** they are logged into that same existing account (no duplicate account created).

---

### User Story 3 - Sign in with Google using an email that already has a password-based account (Priority: P2)

A visitor who already registered with email/password tries "Continue with Google" using a Google account with the same email address.

**Why this priority**: A real, common scenario (someone forgets they used email/password, or wants the faster option later) — without defined behavior here, the feature would break or confuse existing users.

**Independent Test**: Can be fully tested by registering via email/password, then completing the Google consent flow with the same email, and confirming the outcome matches the resolved behavior below.

**Acceptance Scenarios**:

1. **Given** an existing account registered via email/password, **When** the same person completes the Google consent flow with that same email, **Then** they are logged into that same existing account, which becomes accessible via either method (password or Google) from then on — and if that account was previously unverified, it becomes verified as part of this linking, since Google has now confirmed the email.

---

### Edge Cases

- What happens if Google reports the visitor's email as unverified on Google's own side (rare, but possible for some account types)? The system should not trust an unverified email from Google any more than a self-reported one.
- What happens if the visitor closes the Google consent window without completing it?
- What happens if a returning Google user's email address has changed on Google's side since they last logged in?

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: System MUST allow a visitor to register or log in using their Google account via Google's OAuth consent flow ("Continue with Google").
- **FR-002**: System MUST treat the email address Google provides as already verified, and MUST NOT require a separate email verification step for Google-authenticated accounts — unless Google itself reports the email as unverified, in which case the system MUST reject the attempt (see Edge Cases).
- **FR-003**: System MUST automatically create a new, already-verified account the first time someone authenticates via Google with an email not already associated with any account.
- **FR-004**: System MUST recognize a returning Google-authenticated user on subsequent logins and log them into their existing account rather than creating a duplicate.
- **FR-005**: System MUST link Google authentication to an existing email/password account when the Google account's email matches it — the visitor is logged into that same account (not a new one), and that account becomes accessible via either password or Google login from then on. If the existing account was unverified, System MUST mark it verified as part of this linking, since Google has now confirmed ownership of the email.
- **FR-006**: System MUST establish an authenticated session upon successful Google authentication, consistent with the session behavior already defined for email/password login.
- **FR-007**: System MUST handle a cancelled or failed Google authentication attempt gracefully — returning the visitor to the login page with a clear message, without creating a partial or broken account.

### Key Entities *(include if feature involves data)*

- **User**: Existing entity from the email/password authentication feature. This feature adds the ability for a User to also be associated with a Google account identity, so the system can recognize a returning Google-authenticated visitor as the same person.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A new visitor can complete Google-based registration in under 30 seconds.
- **SC-002**: A returning Google user can log in in under 15 seconds.
- **SC-003**: 100% of accounts created via Google are usable (logged in, no verification prompt) immediately, with no separate confirmation email step.
- **SC-004**: 100% of repeat Google sign-ins by the same person land on the same account — no duplicate accounts are ever created for one Google identity.
- **SC-005**: 100% of pre-existing (email/password) accounts that link a matching Google identity become immediately usable via either login method, with no separate re-verification step required.

## Assumptions

- Google is trusted as the authority on whether an email address is verified, since Google itself confirms email ownership before issuing it through the OAuth flow — this is why no separate verification step is required here, unlike the self-reported emails in the email/password feature.
- Setting a password on a Google-authenticated account (to also enable email/password login later) is out of scope for this feature.
- Displaying or storing a Google profile picture/avatar is out of scope for this feature (may be revisited in the profile management feature).
- This feature depends on the email/password authentication feature already being implemented (the `User` entity and account infrastructure it introduced).
