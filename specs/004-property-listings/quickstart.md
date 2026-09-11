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
2. Go to `/listings/create` and submit a new listing with an address, a price, a type (sale or
   rent), bedrooms, bathrooms, size, and a description.
   - **Expected**: redirected to `/listings`, the new listing appears with status `available`.
3. Try submitting the create form again, this time leaving the address blank.
   - **Expected**: validation error naming the missing field; nothing is created.
4. Try submitting a listing with a price of `0` or a negative number.
   - **Expected**: validation error; nothing is created.
5. Open the listing created in step 2 for editing and change its status to `pending`, then save.
   - **Expected**: redirected to `/listings`, the listing now shows status `pending`.
6. Change the same listing's status to `closed`, then save.
   - **Expected**: the listing shows status `closed` and still appears in the list (not removed).
7. Log out, then log in as Agent B (a different account) and go to `/listings`.
   - **Expected**: Agent B's list does not include any of Agent A's listings.
8. While logged in as Agent B, try visiting `/listings/{id}/edit` using the ID of a listing created
   by Agent A in step 2.
   - **Expected**: access denied (403), not the edit form.
