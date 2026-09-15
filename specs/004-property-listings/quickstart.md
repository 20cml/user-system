# Quickstart: Validate Property Listings

## Prerequisites

- MySQL running locally, migrations up to date (`php artisan migrate` includes this feature's new
  `listings` migration once implemented).
- Two separate agent accounts already registered and onboarded (profile complete) — needed to test
  the ownership boundary (FR-006, FR-008). Reuse any existing account plus one new one.

## Setup

```bash
php artisan migrate
npm run build
php artisan serve
```

## Automated validation

```bash
php artisan test --filter=Listing
```

Expected: all tests in `tests/Feature/ListingTest.php` pass, covering creation, validation,
editing, status changes, and the ownership boundary.

## Manual validation walkthrough

1. Log in as Agent A (an existing, fully onboarded account) and go to `/listings`.
   - **Expected**: empty list (or only listings Agent A already created).
2. Go to `/listings/create`. Type a real postal/zip code in the address field.
   - **Expected**: a matching suggestion appears (same behavior as the profile page), and selecting
     it fills city, state/province, and country — the street stays blank for you to type by hand
     (a postal/zip code alone doesn't identify a specific street; see
     `specs/003-profile-completion/research.md`).
3. Submit a new listing with an address (using the autocomplete suggestion, which fills in
   country too), a price, a listing type (sale or rent), a property type, size, and a description —
   attach one photo.
   - **Expected**: redirected to `/listings`; the new listing appears with status `available`, its
     own country reflecting the address entered, its `currency` matching Agent A's own profile
     country (CAD for CA, USD for US) — not necessarily the listing's own country — and its photo
     visible as a thumbnail.
4. Try submitting the create form again, this time leaving the address blank.
   - **Expected**: validation error naming the missing field; nothing is created.
5. Try submitting a listing with a price of `0` or a negative number.
   - **Expected**: validation error; nothing is created.
6. Open the listing created in step 3 for editing and change its status to `pending`, then save.
   - **Expected**: redirected to `/listings`, the listing now shows status `pending`.
7. Change the same listing's status to `closed`, then save.
   - **Expected**: the listing shows status `closed` and still appears in the list (not removed).
8. On the same listing, upload a different photo, then save.
   - **Expected**: the new photo replaces the old one.
9. Click the "×" on the photo and save.
   - **Expected**: the photo is gone from both the edit page and the listing.
10. Log out, then log in as Agent B (a different account) and go to `/listings`.
    - **Expected**: Agent B's list does not include any of Agent A's listings.
11. While logged in as Agent B, try visiting `/listings/{id}/edit` using the ID of a listing created
    by Agent A in step 3.
    - **Expected**: access denied (403), not the edit form.
12. Back as Agent A (with at least two listings of different statuses/prices), apply a status
    filter on `/listings`.
    - **Expected**: only listings matching that status are shown.
13. Add a second filter (e.g., a maximum price) on top of the first.
    - **Expected**: only listings matching both filters are shown.
14. Clear all filters.
    - **Expected**: the full list of Agent A's listings is shown again.
15. Open one of Agent A's listings for editing and click "Delete this listing," confirming the prompt.
    - **Expected**: redirected to `/listings`; that listing (and its photo, if it had one) is gone for good — not just marked "closed."
