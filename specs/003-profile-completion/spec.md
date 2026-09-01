# Feature Specification: Mandatory Profile Completion

**Feature Branch**: `003-profile-completion`

**Created**: 2026-08-31

**Status**: Draft

**Input**: User description: "After a user logs in for the first time — whether by email/password or Google — they must complete their profile (phone number, address, company name) before reaching the dashboard. Address fields can be filled in with help from an autocomplete suggestion as the user types their postal/zip code, for addresses in Canada and the United States. Once the profile is complete, the user goes straight to the dashboard on every future login."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Complete profile on first access (Priority: P1)

A user who just logged in for the very first time — whether by registering with email/password (and verifying it) or by signing up with Google — is sent to a profile page instead of the dashboard, and must fill in their phone number, address, and company name before they can proceed.

**Why this priority**: This is the entire point of the feature — without it, no gate exists and the dashboard remains reachable with an incomplete profile.

**Independent Test**: Can be fully tested by completing registration/verification (or Google sign-up) and confirming the very next page reached is the profile form, not the dashboard, and that the dashboard is unreachable until the form is submitted successfully.

**Acceptance Scenarios**:

1. **Given** a user has just verified their email/password account for the first time, **When** they are redirected after verification, **Then** they land on the profile page instead of the dashboard.
2. **Given** a user has just signed up with Google for the first time, **When** the sign-up completes, **Then** they land on the profile page instead of the dashboard.
3. **Given** a user is on the profile page with an incomplete profile, **When** they try to reach the dashboard directly, **Then** they are sent back to the profile page.
4. **Given** a user fills in phone number, address, and company name and submits, **When** the submission succeeds, **Then** they are taken to the dashboard.
5. **Given** a user submits the profile form with a required field left empty, **When** they submit, **Then** the submission is rejected with a clear indication of what's missing, and they remain on the profile page.

---

### User Story 2 - Address autocomplete while completing the profile (Priority: P2)

While filling in their address, a user in Canada or the United States types their postal/zip code and is offered a matching address to select, so they don't have to type the rest of the address by hand.

**Why this priority**: Speeds up and reduces errors in the most tedious part of the form, but the form must remain usable by typing the address manually — so it's valuable but not blocking.

**Independent Test**: Can be fully tested by typing a known Canadian or U.S. postal/zip code into the address field and confirming a matching address suggestion appears and can be selected to fill in the rest of the address.

**Acceptance Scenarios**:

1. **Given** a user typing their postal/zip code, **When** it matches a known Canadian or U.S. location, **Then** a suggested address is offered.
2. **Given** a suggested address, **When** the user selects it, **Then** the remaining address fields are filled in automatically, and the user may still edit them.
3. **Given** no suggestion is available or the user's address is outside Canada/the U.S., **When** this happens, **Then** the user can still type their full address by hand and submit successfully.

---

### User Story 3 - Never see this screen again once complete (Priority: P2)

A user who has already completed their profile logs in again later and goes straight to the dashboard.

**Why this priority**: Without this, the feature would be a repeated annoyance rather than a one-time setup step.

**Independent Test**: Can be fully tested by completing the profile once, logging out, and logging back in (via either method), confirming the dashboard is reached directly.

**Acceptance Scenarios**:

1. **Given** a user has already completed their profile, **When** they log in again by any method, **Then** they go directly to the dashboard.
2. **Given** a user has already completed their profile, **When** they later choose to view or edit their profile on their own, **Then** they can do so without it blocking their access again.

---

### Edge Cases

- What happens if a user closes the tab or navigates away while the profile form is partially filled? They remain incomplete and are sent back to the profile page on their next login; unsaved values are not retained.
- What happens if the user tries to reach a page other than the dashboard before completing their profile? They are still sent to the profile page first.
- What happens if the address suggestion service has no data for what the user typed? Suggestions simply don't appear, and manual entry remains available.
- What happens if a user edits an address field after selecting a suggestion? The edited value is what gets saved.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: System MUST prevent a user with an incomplete profile from reaching the dashboard, redirecting them to the profile page instead.
- **FR-002**: This MUST apply the first time a user reaches an authenticated area, regardless of whether they signed in via email/password or Google.
- **FR-003**: System MUST require phone number, address, and company name before a profile can be marked complete.
- **FR-004**: System MUST reject a profile submission missing any required field and clearly indicate what's missing.
- **FR-005**: System MUST offer an address suggestion as the user types their postal/zip code, for addresses in Canada and the United States.
- **FR-006**: System MUST fill in the remaining address fields automatically when a suggestion is selected, while still allowing the user to edit them.
- **FR-007**: System MUST allow the profile to be completed via fully manual entry when no suggestion is available or selected.
- **FR-008**: System MUST remember that a profile is complete and MUST NOT redirect that user to the profile page again on later logins.
- **FR-009**: System MUST continue to let a user view and edit their profile afterward without that action re-triggering the mandatory redirect.

### Key Entities *(include if feature involves data)*

- **User**: Gains new profile attributes — phone number, address (including postal/zip code), and company name — plus a marker for whether the profile is complete.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 100% of users reaching the app for the first time, via either sign-in method, see the profile page before the dashboard.
- **SC-002**: 100% of users who have completed their profile reach the dashboard directly on every later login, with no repeat prompts.
- **SC-003**: A user with a Canadian or U.S. address can fill in their address in under 15 seconds using the suggested-address flow.
- **SC-004**: 0% of profiles missing a required field are ever marked complete.

## Assumptions

- Only Canada and the United States are supported for address-suggestion at this time; users elsewhere fill in their address manually.
- Company name is required for every user in this initial version, even if they aren't affiliated with a company.
- This feature governs first-time access going forward; whether to also apply it retroactively to accounts that existed before this feature shipped is a separate decision, out of scope here.
- The address-suggestion feature depends on a third-party service outside this system's direct control; manual entry always remains a valid fallback.
