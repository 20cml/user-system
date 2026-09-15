# Feature Specification: Lead CRM

**Feature Branch**: `005-lead-crm`

**Created**: 2026-09-13

**Status**: Draft

**Input**: User description: "Real estate agents need to track people who are interested in the properties they have listed. An agent should be able to add a new lead with their name, phone, and email, mark which listing(s) they're interested in, and update their status as they move through the sales process (new, qualified, visited, proposal made, closed, or lost). Agents should be able to log notes about their interactions with each lead over time. Each agent only sees and manages their own leads."

## User Scenarios & Testing _(mandatory)_

### User Story 1 - Add a new lead (Priority: P1)

An agent adds a person who has shown interest in one of their properties, entering their first and last name, phone, and email, so the agent has a record of them going forward.

**Why this priority**: This is the foundation of the feature — without the ability to add a lead, there is nothing to track, link to a listing, or follow up on.

**Independent Test**: Can be fully tested by submitting a new lead with a first name and confirming it appears afterward in the agent's list of leads.

**Acceptance Scenarios**:

1. **Given** an agent is logged in, **When** they submit a new lead with a first name, **Then** the lead is saved and appears in their list of leads with status "new".
2. **Given** an agent is adding a new lead, **When** they leave the first name blank and submit, **Then** the submission is rejected with a clear indication of what's missing.

---

### User Story 2 - Mark which listings a lead is interested in (Priority: P1)

An agent links a lead to one or more of their property listings, so it's clear what that person actually wants.

**Why this priority**: Tracking interest in a specific property is the whole point of a real estate CRM — a lead with no linked listing is just a name in a list.

**Independent Test**: Can be fully tested by linking a lead to a listing and confirming that link is visible when viewing the lead afterward.

**Acceptance Scenarios**:

1. **Given** an agent has both a lead and a listing, **When** they mark the lead as interested in that listing, **Then** the listing appears among that lead's interests.
2. **Given** a lead is interested in a listing, **When** the agent marks them as also interested in a second listing, **Then** both listings appear among that lead's interests.

---

### User Story 3 - Update a lead's status through the sales process (Priority: P2)

As an agent's relationship with a lead progresses, the agent updates their status — new, qualified, visited, proposal made, closed, or lost — so the list always reflects where things really stand.

**Why this priority**: Keeping status current is what makes the list useful day to day, but it depends on User Story 1 already existing.

**Independent Test**: Can be fully tested by creating a lead, changing its status, and confirming the new status is reflected when the lead is viewed again.

**Acceptance Scenarios**:

1. **Given** an agent has a lead marked "new", **When** they change its status to "qualified", **Then** the lead reflects "qualified" everywhere it's shown.
2. **Given** an agent has a lead marked "closed" or "lost", **When** they view their list of leads, **Then** that lead still appears (not removed).

---

### User Story 4 - Log notes about interactions with a lead (Priority: P2)

An agent logs a note each time they interact with a lead — a call, a message, a visit — so they never lose track of the conversation.

**Why this priority**: Useful for staying on top of a relationship over time, but the lead is already trackable without it.

**Independent Test**: Can be fully tested by adding a note to a lead and confirming it appears in that lead's history, timestamped, afterward.

**Acceptance Scenarios**:

1. **Given** an agent is viewing a lead, **When** they add a note, **Then** the note is saved and shown with the date it was added.
2. **Given** a lead already has one note, **When** the agent adds a second note, **Then** both notes are shown, most recent first.
3. **Given** a lead has a note the agent no longer wants (e.g., added by mistake), **When** they delete it and confirm, **Then** the note is permanently removed while the rest of the lead's notes remain.

---

### User Story 5 - Manage only your own leads (Priority: P1)

An agent only ever sees and can edit the leads they personally added — never another agent's.

**Why this priority**: Without this boundary, one agent could see or change another agent's client relationships, which is a basic privacy expectation of the system from day one.

**Independent Test**: Can be fully tested by having two different agents each add a lead, then confirming each agent's list only shows their own, and that one agent cannot open or edit the other's lead directly.

**Acceptance Scenarios**:

1. **Given** two agents each have their own leads, **When** either agent views their list of leads, **Then** they only see the leads they personally added.
2. **Given** an agent knows a lead exists that belongs to another agent, **When** they attempt to view, edit, or delete it directly, **Then** access is denied.

### Edge Cases

- What happens when an agent tries to link a lead to a listing that no longer exists (e.g., already deleted)?
- What happens when an agent submits a note with no text?
- How does the system handle a lead linked to a listing that later gets deleted?

## Requirements _(mandatory)_

### Functional Requirements

- **FR-001**: System MUST allow an authenticated agent to create a new lead with a first name, and optionally a last name, phone number, and email.
- **FR-002**: System MUST require a first name before a lead can be saved.
- **FR-003**: System MUST set a newly created lead's status to "new" by default.
- **FR-004**: Agents MUST be able to view the list of leads they have created.
- **FR-005**: Agents MUST be able to edit the details of a lead they created.
- **FR-006**: Agents MUST be able to change a lead's status among "new", "qualified", "visited", "proposal", "closed", and "lost".
- **FR-007**: Agents MUST be able to link a lead to one or more of their own listings, marking interest.
- **FR-008**: Agents MUST be able to add a timestamped note to a lead, viewable afterward alongside that lead's other notes, most recent first.
- **FR-009**: System MUST prevent an agent from viewing, editing, or deleting a lead — or a note on a lead — created by a different agent.
- **FR-010**: System MUST keep a lead's history (i.e., leads marked "closed" or "lost" remain visible, not deleted) once their status changes.
- **FR-011**: Agents MUST be able to permanently delete one of their own leads, as a distinct action from marking it "closed" or "lost".
- **FR-012**: Agents MUST be able to permanently delete a single note from one of their own leads, without affecting that lead's other notes.

### Key Entities

- **Lead**: A person who has shown interest in one or more of an agent's properties. Attributes: first name, last name, phone, email, status (new, qualified, visited, proposal, closed, or lost). Belongs to exactly one agent; may be linked to one or more Listings.
- **Lead Note**: A timestamped note logging an interaction with a lead. Belongs to exactly one Lead.

## Success Criteria _(mandatory)_

### Measurable Outcomes

- **SC-001**: An agent can add a new lead in under 1 minute.
- **SC-002**: 100% of leads are visible only to the agent who created them — never to any other agent.
- **SC-003**: An agent can locate one of their leads and update its status in under 15 seconds.
- **SC-004**: An agent can manage at least 200 of their own leads without any noticeable slowdown in viewing or updating them.

## Assumptions

- Only the individual agent who creates a lead manages it — there is no team or brokerage-level sharing of leads in this version.
- A lead's last name, phone, and email are all optional — only a first name is required, since an agent may only catch someone's first name (or have just one way to reach them) at first.
- A lead can be linked to any number of the agent's own listings; there's no cap.
- "Closed" and "lost" are historical states, not deletion — a lead marked either way stays visible (FR-010); deleting a lead is a separate, explicit action (FR-011) that can't be undone.
- No automated reminders or follow-up notifications are part of this version — notes are a manual log only.
- Where a lead's interest was linked to a listing that later gets deleted, the lead itself is unaffected; only that specific link is gone.
