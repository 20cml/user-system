# Quickstart: Validate Lead CRM

## Prerequisites

- MySQL running locally, migrations up to date (`php artisan migrate` includes this feature's new
  `leads`, `lead_listing`, and `lead_notes` migrations once implemented).
- Two separate agent accounts already registered and onboarded (profile complete) — needed to test
  the ownership boundary (FR-009). Reuse any existing account plus one new one.
- At least one existing `Listing` for Agent A, to link a lead to (created via `/listings/create`).

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

Expected: all tests in `tests/Feature/LeadTest.php` pass, covering creation, validation, linking to
listings, status changes, notes, deletion, and the ownership boundary.

## Manual validation walkthrough

1. Log in as Agent A and go to `/leads`.
   - **Expected**: empty list (or only leads Agent A already created).
1b. With at least one lead already created, type part of its name into the search box at the top.
   - **Expected**: a matching result appears as a link; clicking it opens that lead's edit page
     directly.
2. Go to `/leads/create`. Fill in a first name (last name is optional). In "Interested in," type
   part of an existing listing's address (or its numeric ID) and click the matching suggestion,
   then submit the rest of the lead's details.
   - **Expected**: the suggestion appears as you type; clicking it adds a removable chip; after
     submitting, you're redirected to `/leads` and the new lead appears with status `new`.
3. Try submitting the create form again, this time leaving the first name blank.
   - **Expected**: validation error naming the missing field; nothing is created.
4. Open the lead created in step 2 for editing.
   - **Expected**: the linked listing already shows as a chip; the lead's details are pre-filled.
   Try removing it (click the chip's "×") and adding a different listing via the search box.
5. Change the lead's status to `qualified`, then save.
   - **Expected**: redirected to `/leads`, the lead now shows status `qualified`.
6. Change the same lead's status to `lost`, then save.
   - **Expected**: the lead shows status `lost` and still appears in the list (not removed).
7. Open the lead again and add a note (e.g., "Called on the 13th, wants to see it again").
   - **Expected**: the note appears with today's date; adding a second note shows both, most recent
     first.
7b. Click "Delete" on one of the two notes, confirming the prompt.
   - **Expected**: that note is gone; the other one is still there.
8. Log out, then log in as Agent B (a different account) and go to `/leads`.
   - **Expected**: Agent B's list does not include any of Agent A's leads.
9. While logged in as Agent B, try visiting `/leads/{id}/edit` using the ID of a lead created by
   Agent A in step 2.
   - **Expected**: access denied (403), not the edit form.
10. Back as Agent A, open that lead and click "Delete this lead," confirming the prompt.
    - **Expected**: redirected to `/leads`; the lead (and its notes) is gone for good — not just
      marked `closed`/`lost`.
11. Go to `/listings`, open one of Agent A's listings for editing, and in "Interested leads" type
    part of a lead's name.
    - **Expected**: a matching suggestion appears; clicking it links that lead to the listing —
      the same underlying link as linking from the lead's own page.
