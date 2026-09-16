# Feature Specification: Lead Funnel Automation

**Feature Branch**: `006-lead-funnel-automation`

**Created**: 2026-09-16

**Status**: Draft

**Input**: User description: "Real estate agents need their leads to move through a sales funnel automatically instead of updating status by hand. Each lead has a type (Buyer, Seller, Investor, Renter, or Landlord); for now only Buyer leads get an automated funnel — the others keep their current simple status. A Buyer lead starts as 'New'. It becomes 'Contacted' automatically once the agent logs the first note. It becomes 'Qualified' automatically once a 3-item financing checklist (pre-approval letter, proof of income, ID document) is fully checked. It becomes 'Active Search' automatically once at least one listing is linked to it. These automatic transitions only ever move a lead forward through New → Contacted → Qualified → Active Search, never skipping a stage or moving backward on their own. 'Lost' is a separate, parallel status that can be set manually at any point regardless of which stage the lead is in — once a lead is marked Lost, none of the automatic transitions above should move it out of that status; only the agent can do that by hand. The agent's Dashboard should show a summary count of leads per status."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Classify a lead by type (Priority: P1)

An agent categorizes each lead as a Buyer, Seller, Investor, Renter, or Landlord, so the system knows which kind of client this is and, for Buyers, can apply the automated funnel.

**Why this priority**: Every other story depends on knowing the lead's type first — it's the gate that determines whether the automated funnel applies at all.

**Independent Test**: Can be fully tested by setting a lead's type to each of the five values and confirming it's saved and displayed correctly, independent of any funnel behavior.

**Acceptance Scenarios**:

1. **Given** an agent is creating or editing a lead, **When** they choose a type from Buyer, Seller, Investor, Renter, or Landlord, **Then** the lead is saved with that type.
2. **Given** a lead has no type set (existing leads created before this feature), **When** the agent views it, **Then** the system treats it as a non-Buyer type (no automated funnel applies) until the agent sets a type.

---

### User Story 2 - Buyer lead advances through the funnel automatically (Priority: P2)

A Buyer lead's status moves forward on its own — New → Contacted → Qualified → Active Search — as the agent does the normal work of following up, verifying financing, and sharing listings, without the agent ever opening a status dropdown.

**Why this priority**: This is the core value of the feature — replacing manual status upkeep with status that reflects what actually happened.

**Independent Test**: Can be fully tested by creating a Buyer lead and, in order, adding a note, completing the financing checklist, and linking a listing — confirming the status advances after each step without manual intervention.

**Acceptance Scenarios**:

1. **Given** a new Buyer lead with status 'New', **When** the agent adds the lead's first note, **Then** the lead's status automatically becomes 'Contacted'.
2. **Given** a Buyer lead with status 'Contacted', **When** the agent marks all 3 financing checklist items (pre-approval letter, proof of income, ID document) as complete, **Then** the lead's status automatically becomes 'Qualified'.
3. **Given** a Buyer lead with status 'Qualified', **When** the agent links at least one listing to the lead, **Then** the lead's status automatically becomes 'Active Search'.
4. **Given** a Buyer lead with status 'New' (no notes yet), **When** the agent links a listing to it, **Then** the lead's status does NOT change to 'Active Search' (it isn't Qualified yet).
5. **Given** a Buyer lead with status 'New', **When** the agent completes the financing checklist before adding any note, **Then** the lead's status does NOT jump to 'Qualified' — it stays 'New' until a first note moves it to 'Contacted', at which point the already-complete checklist takes effect and it becomes 'Qualified'.
6. **Given** a Buyer lead already at 'Active Search', **When** the agent removes the only note or unchecks a financing checklist item, **Then** the lead's status does NOT move backward.

---

### User Story 3 - Marking a lead Lost overrides the automated funnel (Priority: P2)

An agent marks a lead as Lost the moment they know it's no longer going anywhere, regardless of where it was in the funnel — and it stays Lost even if the agent later adds a note, finishes the checklist, or links a listing.

**Why this priority**: Without this, the automation could silently "revive" a lead the agent has explicitly given up on, which would undermine trust in the automation.

**Independent Test**: Can be fully tested by marking leads Lost from several different starting statuses and then performing actions that would normally advance the funnel, confirming status stays Lost.

**Acceptance Scenarios**:

1. **Given** a Buyer lead at any status (New, Contacted, Qualified, or Active Search), **When** the agent manually sets its status to 'Lost', **Then** the status becomes 'Lost'.
2. **Given** a Buyer lead marked 'Lost', **When** the agent adds a note, completes the financing checklist, or links a listing, **Then** the lead's status remains 'Lost'.
3. **Given** a Buyer lead marked 'Lost', **When** the agent manually changes its status to something else, **Then** the status changes as the agent chose (manual changes are never blocked).

---

### User Story 4 - See lead counts by status on the Dashboard (Priority: P3)

An agent glances at the Dashboard and sees how many leads are in each status, without having to open the full leads list and count manually.

**Why this priority**: This is the payoff of having accurate, automatic statuses — a trustworthy funnel is only useful if the agent can see it at a glance.

**Independent Test**: Can be fully tested by creating leads with a mix of statuses and confirming the Dashboard shows the correct count for each.

**Acceptance Scenarios**:

1. **Given** an agent has leads in several different statuses, **When** they view the Dashboard, **Then** they see a count of their own leads for each status.
2. **Given** an agent has no leads yet, **When** they view the Dashboard, **Then** the summary shows zero counts rather than an error or blank section.

---

### Edge Cases

- What happens when an agent completes the financing checklist and links a listing in the same action, before any note exists? The status advances only as far as the rules allow in order — it cannot skip past 'Contacted' to reach 'Qualified' or 'Active Search' until a first note exists.
- What happens when an agent unchecks a financing checklist item after the lead reached 'Qualified' or beyond? The status does not move backward; automatic transitions only ever move a lead forward.
- What happens when an agent changes a lead's type away from Buyer after it has already advanced through part of the funnel? The lead keeps its current status, but no further automatic transitions apply since the funnel is Buyer-only.
- What happens when an agent unlinks the only listing from a lead at 'Active Search'? The status does not move backward.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: System MUST allow an agent to set a lead's type to one of: Buyer, Seller, Investor, Renter, or Landlord.
- **FR-002**: System MUST default a new lead's status to 'New'.
- **FR-003**: For leads of type Buyer, system MUST automatically change status from 'New' to 'Contacted' when the agent adds the lead's first note, provided the lead has not been marked 'Lost'.
- **FR-004**: System MUST provide a 3-item financing checklist (pre-approval letter, proof of income, ID document) for Buyer leads.
- **FR-005**: For leads of type Buyer, system MUST automatically change status to 'Qualified' when all 3 financing checklist items are marked complete, provided the lead's current status is already 'Contacted' and it has not been marked 'Lost'.
- **FR-006**: For leads of type Buyer, system MUST automatically change status to 'Active Search' when at least one listing is linked to the lead, provided the lead's current status is already 'Qualified' and it has not been marked 'Lost'.
- **FR-007**: The automatic transitions in FR-003, FR-005, and FR-006 MUST NOT skip a stage or move a lead backward.
- **FR-008**: Agents MUST be able to manually set any lead's status to 'Lost' at any point, regardless of its current status.
- **FR-009**: Once a lead's status is 'Lost', system MUST NOT apply any of the automatic transitions in FR-003, FR-005, or FR-006 — only a manual status change by the agent can move it out of 'Lost'.
- **FR-010**: Agents MUST retain the ability to manually set a Buyer lead's status to any value at any time; the automatic transitions are a convenience on top of manual control, never a restriction on it.
- **FR-011**: For leads of type Seller, Investor, Renter, or Landlord, system MUST continue using the existing manual status flow (New, Qualified, Visited, Proposal, Closed, Lost) with no automatic transitions.
- **FR-012**: The agent's Dashboard MUST display a count of the agent's own leads grouped by status.

### Key Entities

- **Lead**: gains a `type` attribute (Buyer, Seller, Investor, Renter, or Landlord) alongside its existing name, phone, email, and status.
- **Financing Checklist**: three yes/no items tied to a single Buyer lead — pre-approval letter, proof of income, ID document — used to determine when that lead becomes 'Qualified'.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 100% of New Buyer leads automatically show status Contacted immediately after their first note is added, with zero manual status edits required.
- **SC-002**: 100% of Contacted Buyer leads automatically show status Qualified immediately after all 3 financing checklist items are marked complete, with zero manual status edits required.
- **SC-003**: 100% of Qualified Buyer leads automatically show status Active Search immediately after a listing is linked; 0% of leads below Qualified change status when a listing is linked to them.
- **SC-004**: 100% of leads marked Lost remain Lost through at least three subsequent actions that would normally advance status (a note added, the checklist completed, a listing linked).
- **SC-005**: An agent can see how many leads they have in each status without leaving the Dashboard or opening the leads list.

## Assumptions

- Only Buyer-type leads get an automated funnel in this feature; Seller, Investor, Renter, and Landlord funnels are future work (not in scope here).
- The financing checklist items are fixed (pre-approval letter, proof of income, ID document) and not customizable per agent.
- "First note" means the earliest note recorded for that lead.
- Marking a lead Lost does not delete or hide it — it remains visible like any other lead.
- Showing, Offer, Under Contract, and Closed/Won stages of the Buyer funnel are out of scope for this feature (planned for a future feature).
- Existing leads (created before this feature) have no type until an agent sets one, and are treated as non-Buyer (no automated funnel) until then.
