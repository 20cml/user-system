<?php

namespace App\Http\Controllers;

use App\Http\Requests\LeadRequest;
use App\Models\Lead;
use App\Models\LeadNote;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeadController extends Controller
{
    use AuthorizesRequests;

    /**
     * List the logged-in agent's own leads, newest first.
     */
    public function index(Request $request): View
    {
        $leads = $request->user()->leads()->latest();

        if ($statuses = $request->query('status')) {
            $leads->whereIn('status', (array) $statuses);
        }

        if ($name = trim((string) $request->query('name'))) {
            $leads->where(function ($builder) use ($name) {
                $builder->where('first_name', 'like', "%{$name}%")
                    ->orWhere('last_name', 'like', "%{$name}%");
            });
        }

        return view('leads.index', [
            'leads' => $leads->get(),
        ]);
    }

    /**
     * Show the form for adding a new lead.
     */
    public function create(): View
    {
        return view('leads.create');
    }

    /**
     * Create a new lead owned by the logged-in agent.
     */
    public function store(LeadRequest $request): RedirectResponse
    {
        $lead = $request->user()->leads()->create([
            ...$request->safe()->except(['status', 'listing_ids']),
            'status' => 'new',
        ]);

        $lead->listings()->sync($request->safe()->input('listing_ids', []));
        $lead->advanceBuyerFunnel();

        return redirect()->route('leads.index');
    }

    /**
     * Show the form for editing an existing lead.
     */
    public function edit(Lead $lead): View
    {
        $this->authorize('update', $lead);

        return view('leads.edit', [
            'lead' => $lead->load('listings', 'notes'),
        ]);
    }

    /**
     * Update an existing lead's details, status, and/or linked listings.
     */
    public function update(LeadRequest $request, Lead $lead): RedirectResponse
    {
        $this->authorize('update', $lead);

        $lead->update([
            ...$request->safe()->except(['listing_ids', 'financing_preapproval', 'financing_income_proof', 'financing_id_document']),
            'financing_preapproval' => $request->boolean('financing_preapproval'),
            'financing_income_proof' => $request->boolean('financing_income_proof'),
            'financing_id_document' => $request->boolean('financing_id_document'),
        ]);
        $lead->listings()->sync($request->safe()->input('listing_ids', []));
        $lead->advanceBuyerFunnel();

        return redirect()->route('leads.index');
    }

    /**
     * Permanently delete a lead.
     */
    public function destroy(Lead $lead): RedirectResponse
    {
        $this->authorize('delete', $lead);

        $lead->delete();

        return redirect()->route('leads.index');
    }

    /**
     * Add a timestamped note to a lead.
     */
    public function storeNote(Request $request, Lead $lead): RedirectResponse
    {
        $this->authorize('update', $lead);

        $request->validate(['body' => ['required', 'string']]);

        $lead->notes()->create(['body' => $request->input('body')]);
        $lead->advanceBuyerFunnel();

        return redirect()->route('leads.edit', $lead);
    }

    /**
     * Permanently delete a note from a lead.
     */
    public function destroyNote(Lead $lead, LeadNote $note): RedirectResponse
    {
        $this->authorize('update', $lead);

        abort_unless($note->lead_id === $lead->id, 404);

        $note->delete();

        return redirect()->route('leads.edit', $lead);
    }
}
