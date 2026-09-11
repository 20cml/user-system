# Feature Specification: Property Listings

**Feature Branch**: `004-property-listings`

**Created**: 2026-09-10

**Status**: Draft

**Input**: User description: "Real estate agents need to keep track of the properties they have available to sell or rent. An agent should be able to add a new property listing with its address, price, type (for sale or for rent), number of bedrooms and bathrooms, size, and a description, and mark its status as available, pending, or closed. Each agent only sees and manages their own listings."

## User Scenarios & Testing _(mandatory)_

### User Story 1 - Add a new property listing (Priority: P1)

An agent adds a property they have available to sell or rent, entering its address, price, type (for sale or for rent), number of bedrooms and bathrooms, size, and a description, so it's tracked in the system going forward.

**Why this priority**: This is the foundation of the feature — without the ability to add a listing, there is nothing to track, view, or update.

**Independent Test**: Can be fully tested by submitting a new listing with all required details and confirming it appears afterward in the agent's list of properties.

**Acceptance Scenarios**:

1. **Given** an agent is logged in, **When** they submit a new listing with address, price, type, bedrooms, bathrooms, size, and description, **Then** the listing is saved and appears in their list of properties with status "available".
2. **Given** an agent is adding a new listing, **When** they leave a required field empty and submit, **Then** the submission is rejected with a clear indication of what's missing.
3. **Given** an agent is adding a new listing, **When** they enter a price of zero or a negative number, **Then** the submission is rejected.

---

### User Story 2 - Update a listing's status (Priority: P2)

As a deal progresses, an agent updates a listing's status — from available, to pending (an offer is in progress), to closed (sold or rented) — so their list always reflects where each property really stands.

**Why this priority**: Keeping status current is what makes the list useful day to day, but it depends on User Story 1 already existing.

**Independent Test**: Can be fully tested by creating a listing, changing its status, and confirming the new status is reflected when the listing is viewed again.

**Acceptance Scenarios**:

1. **Given** an agent has an existing listing marked "available", **When** they change its status to "pending", **Then** the listing reflects "pending" everywhere it's shown.
2. **Given** an agent has a listing marked "pending", **When** they change its status to "closed", **Then** the listing reflects "closed" and remains visible in their list (not deleted).

---

### User Story 3 - Manage only your own listings (Priority: P1)

An agent only ever sees and can edit the property listings they personally added — never another agent's.

**Why this priority**: Without this boundary, one agent could see or change another agent's clients' data, which is a basic privacy expectation of the system from day one.

**Independent Test**: Can be fully tested by having two different agents each add a listing, then confirming each agent's list only shows their own, and that one agent cannot open or edit the other's listing directly.

**Acceptance Scenarios**:

1. **Given** two agents each have their own listings, **When** either agent views their list of properties, **Then** they only see the listings they personally added.
2. **Given** an agent knows a listing exists that belongs to another agent, **When** they attempt to view or edit it directly, **Then** access is denied.

### Edge Cases

- What happens when an agent tries to edit or update the status of a listing that no longer exists (e.g., already removed)?
- How does the system handle a listing address that looks incomplete or malformed (e.g., missing city)?
- What happens when an agent submits a very large description?

## Requirements _(mandatory)_

### Functional Requirements

- **FR-001**: System MUST allow an authenticated agent to create a new property listing with: address, price, listing type (for sale or for rent), number of bedrooms, number of bathrooms, size, and a description.
- **FR-002**: System MUST require address, price, and listing type before a listing can be saved.
- **FR-003**: System MUST reject a listing price that is zero or negative.
- **FR-004**: System MUST set a newly created listing's status to "available" by default.
- **FR-005**: Agents MUST be able to view the list of property listings they have created.
- **FR-006**: Agents MUST be able to edit the details of a listing they created.
- **FR-007**: Agents MUST be able to change a listing's status among "available", "pending", and "closed".
- **FR-008**: System MUST prevent an agent from viewing or editing a listing created by a different agent.
- **FR-009**: System MUST keep a listing's history (i.e., closed listings remain visible, not deleted) once its status changes to "closed".

### Key Entities

- **Listing**: A property an agent has available to sell or rent. Attributes: address, price, listing type (sale or rent), status (available, pending, or closed), number of bedrooms, number of bathrooms, size, and description. Belongs to exactly one agent.

## Success Criteria _(mandatory)_

### Measurable Outcomes

- **SC-001**: An agent can add a new property listing in under 2 minutes.
- **SC-002**: 100% of listings are visible only to the agent who created them — never to any other agent.
- **SC-003**: An agent can locate one of their listings and update its status in under 30 seconds.
- **SC-004**: An agent can manage at least 200 of their own listings without any noticeable slowdown in viewing or updating them.

## Assumptions

- Only the individual agent who creates a listing manages it — there is no team or brokerage-level sharing of listings in this version.
- Property size is recorded in square meters.
- Listings are added manually by the agent; importing listings from other sources or pulling them from an external API is out of scope for this version.
- Photo uploads for a listing are out of scope for this version.
- Address entry does not require autocomplete assistance in this version (unlike the existing profile page).
