# Feature Specification: Property Listings

**Feature Branch**: `004-property-listings`

**Created**: 2026-09-10

**Status**: Draft

**Input**: User description: "Real estate agents need to keep track of the properties they have available to sell or rent. An agent should be able to add a new property listing with its address, price, type (for sale or for rent), number of bedrooms and bathrooms, size, and a description, and mark its status as available, pending, or closed. Each agent only sees and manages their own listings."

## User Scenarios & Testing _(mandatory)_

### User Story 1 - Add a new property listing (Priority: P1)

An agent adds a property they have available to sell or rent, entering its address, price, listing type (for sale or for rent), property type (house, apartment, land, or commercial), size, and a description, so it's tracked in the system going forward.

**Why this priority**: This is the foundation of the feature — without the ability to add a listing, there is nothing to track, view, or update.

**Independent Test**: Can be fully tested by submitting a new listing with all required details and confirming it appears afterward in the agent's list of properties.

**Acceptance Scenarios**:

1. **Given** an agent is logged in, **When** they submit a new listing with address, price, listing type, property type, size, and description, **Then** the listing is saved and appears in their list of properties with status "available".
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
3. **Given** an agent wants a listing gone entirely rather than marked "closed" (e.g., it was added by mistake), **When** they delete it and confirm, **Then** the listing is permanently removed from their list.

---

### User Story 3 - Manage only your own listings (Priority: P1)

An agent only ever sees and can edit the property listings they personally added — never another agent's.

**Why this priority**: Without this boundary, one agent could see or change another agent's clients' data, which is a basic privacy expectation of the system from day one.

**Independent Test**: Can be fully tested by having two different agents each add a listing, then confirming each agent's list only shows their own, and that one agent cannot open or edit the other's listing directly.

**Acceptance Scenarios**:

1. **Given** two agents each have their own listings, **When** either agent views their list of properties, **Then** they only see the listings they personally added.
2. **Given** an agent knows a listing exists that belongs to another agent, **When** they attempt to view, edit, or delete it directly, **Then** access is denied.

### User Story 4 - Attach a photo to a listing (Priority: P2)

An agent attaches a single photo to a listing — while creating it or afterward — and can replace or remove it later.

**Why this priority**: A photo makes a listing far more useful to look at, but the listing is already functional (trackable, editable) without one, so this builds on User Story 1 rather than blocking it.

**Independent Test**: Can be fully tested by uploading a photo to a listing, confirming it displays, then replacing it with a different one and removing it, confirming each change is reflected.

**Acceptance Scenarios**:

1. **Given** an agent is creating or editing a listing, **When** they upload a photo, **Then** the photo is saved and shown with the listing.
2. **Given** a listing already has a photo, **When** the agent uploads a new one, **Then** the new photo replaces the old one.
3. **Given** a listing has a photo, **When** the agent removes it and saves, **Then** the photo is deleted and no longer shown.

### User Story 5 - Filter the list of listings (Priority: P3)

An agent optionally narrows down their list of listings by status, listing type, property type,
and/or a minimum/maximum price, in any combination — leaving every filter untouched shows the full
list, exactly as it does today.

**Why this priority**: Useful once an agent has enough listings (especially with closed ones never
disappearing, per FR-009) that scrolling through everything gets tedious, but the list is already
fully usable without it.

**Independent Test**: Can be fully tested by creating listings with different statuses/types/prices,
applying one or more filters, and confirming only the matching listings show — then clearing the
filters and confirming the full list returns.

**Acceptance Scenarios**:

1. **Given** an agent has listings with different statuses, **When** they select a status filter, **Then** only listings matching that status are shown.
2. **Given** an agent sets more than one filter at the same time (e.g., listing type and a maximum price), **When** they apply them, **Then** only listings matching all of the selected filters are shown.
3. **Given** an agent has applied one or more filters, **When** they clear them, **Then** the full list is shown again.

### Edge Cases

- What happens when an agent tries to edit or update the status of a listing that no longer exists (e.g., already removed)?
- How does the system handle a listing address that looks incomplete or malformed (e.g., missing city)?
- What happens when an agent submits a very large description?

## Requirements _(mandatory)_

### Functional Requirements

- **FR-001**: System MUST allow an authenticated agent to create a new property listing with: address, price, listing type (for sale or for rent), property type (house, apartment, land, or commercial), size, and a description.
- **FR-002**: System MUST require address, price, and listing type before a listing can be saved.
- **FR-003**: System MUST reject a listing price that is zero or negative.
- **FR-004**: System MUST set a newly created listing's status to "available" by default.
- **FR-005**: Agents MUST be able to view the list of property listings they have created.
- **FR-006**: Agents MUST be able to edit the details of a listing they created.
- **FR-007**: Agents MUST be able to change a listing's status among "available", "pending", and "closed".
- **FR-008**: System MUST prevent an agent from viewing, editing, or deleting a listing created by a different agent.
- **FR-009**: System MUST keep a listing's history (i.e., closed listings remain visible, not deleted) once its status changes to "closed".
- **FR-019**: Agents MUST be able to permanently delete one of their own listings, as a distinct action from marking it "closed".
- **FR-010**: System MUST determine the currency shown for a listing's price from the agent's own profile country (CAD for a Canada-based agent, USD for a U.S.-based agent) rather than accepting a separately entered currency.
- **FR-011**: Agents MUST be able to attach a single photo to a listing.
- **FR-012**: System MUST replace a listing's existing photo when the agent uploads a new one, rather than keeping both.
- **FR-013**: Agents MUST be able to remove a photo from one of their listings.
- **FR-015**: System MUST assist the agent's entry of a listing's address — including its country — with the same autocomplete suggestion used on the existing profile page.
- **FR-016**: System MUST list an agent's listings ordered from most recently added to least recently added, showing the date each was added.
- **FR-017**: Agents MUST be able to filter their list of listings by status, listing type, property type, and/or a minimum and/or maximum price, in any combination.
- **FR-018**: System MUST show the agent's full list of listings when no filter is applied.

### Key Entities

- **Listing**: A property an agent has available to sell or rent. Attributes: address (including its own country), price, currency (derived from the agent's own profile country), listing type (sale or rent), property type (house, apartment, land, or commercial), status (available, pending, or closed), size, description, and an optional photo. Belongs to exactly one agent.

## Success Criteria _(mandatory)_

### Measurable Outcomes

- **SC-001**: An agent can add a new property listing in under 2 minutes.
- **SC-002**: 100% of listings are visible only to the agent who created them — never to any other agent.
- **SC-003**: An agent can locate one of their listings and update its status in under 30 seconds.
- **SC-004**: An agent can manage at least 200 of their own listings without any noticeable slowdown in viewing or updating them.

## Assumptions

- Only the individual agent who creates a listing manages it — there is no team or brokerage-level sharing of listings in this version.
- "Closed" and "deleted" are two different things: closing a listing keeps it as a historical
  record (FR-009); deleting it removes it entirely (FR-019). Deletion asks for confirmation first,
  since it can't be undone.
- Property size is recorded in square meters.
- A listing's own country reflects where the *property* actually is (entered as part of its
  address, assisted by autocomplete) and can differ from the agent's own profile country — a
  Canada-based agent could, in principle, list a U.S. property. The currency shown for the price is
  based on the *agent's* profile country, not the listing's own, since currency is about how the
  agent conducts business rather than the specific property's location.
- Listings are added manually by the agent; importing listings from other sources or pulling them from an external API is out of scope for this version.
- Address entry is assisted by the same autocomplete suggestion used on the existing profile page.
- A listing may have at most one photo; attaching a new one replaces whatever photo was there before, rather than keeping a gallery.
- The photo is stored on the server's own file storage; no third-party image hosting is used in this version.
- An uploaded photo is stored as-is — no resizing, cropping, or compression is performed in this version.
