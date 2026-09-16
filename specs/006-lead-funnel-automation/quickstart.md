# Quickstart: Validate Lead Funnel Automation

## Prerequisites

- MySQL running locally, migrations up to date (`php artisan migrate` includes this feature's new
  columns on `leads` once implemented).
- At least one agent account, onboarded (profile complete).
- At least one existing `Listing` for that agent, to link a lead to.

## Setup

```bash
php artisan migrate
npm run build
php artisan serve
```

## Automated validation

```bash
php artisan test --filter=Lead
```

Expected: every test in `tests/Feature/LeadTest.php` passes, including the new tests covering each
acceptance scenario below.

## Manual validation walkthrough

1. Go to `/leads/create`. Set "Lead Type" to Buyer, fill in a first name, and submit.
   - **Expected**: redirected to `/leads`; the new lead shows status `New`.
2. Open the lead for editing and add a note (e.g., "Left a voicemail").
   - **Expected**: after saving the note, the lead's status shows `Contacted` — you never touched the
     status field yourself.
3. Still on the same lead, check all 3 boxes in the financing checklist (pre-approval letter, proof
   of income, ID document) and save.
   - **Expected**: status becomes `Qualified`.
4. In "Interested in," link one of the agent's own listings, and save.
   - **Expected**: status becomes `Active Search`.
5. Create a second Buyer lead. Before adding any note, check all 3 financing checklist boxes and
   save.
   - **Expected**: status stays `New` (it hasn't been Contacted yet — the checklist alone doesn't
     skip ahead).
6. Add a note to that same lead.
   - **Expected**: status jumps straight to `Qualified` (it passes through Contacted and immediately
     satisfies the already-complete checklist in the same step).
7. On any Buyer lead, manually set status to `Lost` and save.
   - **Expected**: status shows `Lost`.
8. On that same Lost lead, add another note.
   - **Expected**: status stays `Lost` — it does not move to `Contacted`.
9. Create a lead with type Seller (or leave type blank) and confirm its status `<select>` still shows
   the original six options (New, Qualified, Visited, Proposal, Closed, Lost), not Contacted/Active
   Search.
10. Go to `/dashboard`.
    - **Expected**: a summary shows how many of the agent's leads are in each status, matching what
      `/leads` shows when filtered by each one.
