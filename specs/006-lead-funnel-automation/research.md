# Phase 0 Research: Lead Funnel Automation

## Decision: Financing checklist is 3 plain boolean columns on `leads`, not a separate table

**Rationale**: The 3 items (pre-approval letter, proof of income, ID document) are fixed, carry no
metadata of their own (no date submitted, no file attachment, no per-item note), and always belong
to exactly one Buyer lead. A separate `lead_financing_checklist` table/model would only add a join
for no benefit — three `boolean` columns directly on `leads` (`financing_preapproval`,
`financing_income_proof`, `financing_id_document`) are simpler and match constitution Principle I.

**Alternatives considered**:
- **A dedicated `LeadFinancingDocument` table** (one row per document) — would make sense if the
  checklist needed to grow, vary per lead, or track file uploads/dates later. Not needed for a fixed
  3-item yes/no checklist; can be introduced later without touching `Lead`'s other columns if the
  checklist ever needs that.

## Decision: One method, `Lead::advanceBuyerFunnel()`, holds all the forward-progression rules

**Rationale**: Three different places in the app can affect a Buyer lead's funnel eligibility —
adding the first note (`LeadController::storeNote()`), completing the financing checklist
(`LeadController::update()`), and linking a listing (from either `LeadController` or
`ListingController`, since the lead↔listing link can be edited from both sides). Rather than
duplicating the same "is it a Buyer, is it not Lost, has this condition now been met" checks at
each call site, a single model method re-evaluates all three transitions in order, called after any
of those actions:

```php
public function advanceBuyerFunnel(): void
{
    if ($this->type !== 'buyer' || $this->status === 'lost') {
        return;
    }

    if ($this->status === 'new' && $this->notes()->exists()) {
        $this->status = 'contacted';
    }

    if ($this->status === 'contacted' && $this->financing_preapproval
            && $this->financing_income_proof && $this->financing_id_document) {
        $this->status = 'qualified';
    }

    if ($this->status === 'qualified' && $this->listings()->exists()) {
        $this->status = 'active_search';
    }

    $this->save();
}
```

Because each `if` re-reads `$this->status` (which the prior `if` may have just updated), the method
naturally respects "never skip a stage" — it only ever advances one step per condition, and each
step's condition is gated on already being at the step before it.

**Deliberate consequence — the funnel can advance more than one stage in a single call.** If a
listing was linked to a lead back when it was still 'New' or 'Contacted' (which does NOT trigger
Active Search yet, per FR-006), and the lead later becomes 'Qualified' via the financing checklist,
that same `advanceBuyerFunnel()` call will also immediately advance it to 'Active Search' in the
same step, since the "has a listing" condition is already true. This is simpler and more predictable
than trying to track "was a listing linked *after* becoming Qualified specifically" — the method
always re-derives the correct status from current data, rather than reacting to one single event
type at a time.

**Alternatives considered**:
- **Laravel model events/observers** (`Lead::updated()`, `LeadNote::created()`, etc.) — would fire
  automatically without explicit calls in controllers, but makes the trigger points implicit and
  harder to trace when reading a controller method, which cuts against constitution Principle V
  (Explainable Code). Calling `advanceBuyerFunnel()` explicitly, right where the triggering action
  happens, keeps the cause and effect visible in the same method.
- **Duplicating the checks at each call site** — rejected as the obvious source of drift (a rule
  changed in one place and not the others).

## Decision: The financing checklist is edited as part of the existing lead edit form

**Rationale**: The 3 checkboxes are just more fields on a Buyer lead, shown only when `type` is
`buyer` (Seller/Investor/Renter/Landlord leads never see them). They're validated and saved by the
existing `LeadController::update()` action — no new route or controller method needed.
`advanceBuyerFunnel()` is called at the end of `update()`, same as it is at the end of `storeNote()`.

**Alternatives considered**:
- **A separate `PATCH /leads/{lead}/financing` endpoint** — would mirror the notes endpoint pattern,
  but notes are a genuinely separate, growing history; the checklist is just 3 fields on the lead
  itself, replaced wholesale on save like every other lead field. No reason to split it out.

## Decision: `type` is a nullable string column, existing leads default to "no type"

**Rationale**: Matches the existing `status` column's convention (a plain validated `string`, not a
database `enum`). Existing leads (created before this feature) get `type = null`; per spec.md's
assumption, a lead with no type is treated as not-Buyer, so `advanceBuyerFunnel()` simply no-ops for
them (`$this->type !== 'buyer'` is true when `type` is `null`) — no backfill migration needed.

**Alternatives considered**:
- **Making `type` required with a default of `buyer`** — would silently opt every existing lead into
  a funnel their data was never set up for (e.g., a lead already 'Qualified' by the old manual
  process, but with none of the 3 financing fields actually checked, would look "stuck"). Leaving it
  nullable and inert for existing leads avoids surprising status side effects on data that predates
  this feature.

## Decision: The Dashboard's status summary is a single query in the existing route closure

**Rationale**: `/dashboard` is currently a plain closure route (`routes/web.php`), not a controller.
Adding one grouped-count query (`$request->user()->leads()->selectRaw('status, count(*) as total')
->groupBy('status')->pluck('total', 'status')`) to that closure is simpler than introducing a
`DashboardController` for a single read. If the Dashboard grows more summaries in a future feature,
promoting it to a controller then is a small, isolated change.

**Alternatives considered**:
- **A new `DashboardController`** — more conventional for a growing dashboard, but not justified yet
  for one query; would be premature structure for what constitution Principle I calls out.
