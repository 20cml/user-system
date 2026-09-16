# Contracts: Behavior Changes to Existing Routes

This feature adds no new routes. It changes what a few existing routes from Feature 5 do after
they succeed, and adds fields to two existing forms.

| Method | Path | What's new |
|---|---|---|
| GET | `/leads/create` | Form gains a `type` select (Buyer/Seller/Investor/Renter/Landlord, optional) |
| POST | `/leads` | Accepts `type`; new Buyer leads start at `status = new` as before — no funnel fields to set yet since there are no notes/checklist/listings at creation time |
| GET | `/leads/{lead}/edit` | Form gains the `type` select; if `type = buyer`, also shows the 3-item financing checklist. The status `<select>` shows only the options valid for the lead's current `type` (see `data-model.md`) |
| PATCH | `/leads/{lead}` | Accepts `type` and the 3 checklist booleans. After saving, calls `Lead::advanceBuyerFunnel()` — may change `status` as a side effect, on top of whatever the agent submitted |
| POST | `/leads/{lead}/notes` | After creating the note, calls `Lead::advanceBuyerFunnel()` — may move a New Buyer lead to Contacted |
| POST | `/listings` and PATCH | `/listings/{listing}` | After syncing `lead_ids`, calls `Lead::advanceBuyerFunnel()` on every lead now linked to the listing — may move a Qualified Buyer lead to Active Search |
| GET | `/dashboard` | Now also passes a lead-count-by-status summary to the view (existing route, no path/method change) |

## Note on `advanceBuyerFunnel()` as a side effect

None of the above routes change their success/failure response shape — a `PATCH /leads/{lead}` still
redirects to `/leads` on success, `POST /leads/{lead}/notes` still redirects back to the edit page,
etc. The only difference is that `status` may now have advanced further than what the agent
explicitly submitted, because the funnel re-evaluates after the action completes. This mirrors the
existing pattern where, e.g., linking a listing already updates `lead_listing` as a side effect of
saving a lead or a listing — `advanceBuyerFunnel()` is one more side effect in that same spot, not a
new kind of request/response.

## Note on ownership (unchanged)

Every route above already sits behind `LeadPolicy`/ownership checks from Feature 5 — this feature
adds no new authorization surface, since it only adds fields and a status side effect to actions an
agent could already perform on their own leads.
