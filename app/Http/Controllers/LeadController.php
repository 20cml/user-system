<?php

namespace App\Http\Controllers;

use App\Http\Requests\LeadRequest;
use App\Models\Lead;
use App\Models\LeadNote;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeadController extends Controller
{
    use AuthorizesRequests;

    /**
     * List the logged-in agent's own leads, alphabetically by name.
     */
    public function index(Request $request): View|JsonResponse
    {
        $leads = $request->user()->leads()->orderBy('first_name')->orderBy('last_name');

        if ($statuses = $request->query('status')) {
            $leads->whereIn('status', (array) $statuses);
        }

        if ($types = $request->query('type')) {
            $leads->whereIn('type', (array) $types);
        }

        if ($name = trim((string) $request->query('name'))) {
            $leads->where(function ($builder) use ($name) {
                $builder->where('first_name', 'like', "%{$name}%")
                    ->orWhere('last_name', 'like', "%{$name}%");
            });
        }

        $leads = $leads->get();

        if ($request->ajax()) {
            return response()->json([
                'rows' => view('leads.partials.rows', ['leads' => $leads])->render(),
                'count' => $leads->count(),
            ]);
        }

        return view('leads.index', [
            'leads' => $leads,
        ]);
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

        return redirect()->route('leads.edit', $lead);
    }

    /**
     * Show the form for editing an existing lead.
     */
    public function edit(Lead $lead): View
    {
        $this->authorize('update', $lead);

        return view('leads.edit', [
            'lead' => $lead->load('listings', 'notes', 'documents'),
        ]);
    }

    /**
     * Update an existing lead's details, status, and/or linked listings.
     */
    public function update(LeadRequest $request, Lead $lead): RedirectResponse
    {
        $this->authorize('update', $lead);

        $lead->update($request->safe()->except(['listing_ids', 'documents']));
        $lead->listings()->sync($request->safe()->input('listing_ids', []));
        $lead->listings()->get()->each->syncStatusFromLeads();

        $submittedDocuments = $request->input('documents', []);
        $allDocumentKeys = collect(Lead::DOCUMENTS_BY_TYPE[$lead->type][$lead->property_type] ?? [])
            ->flatMap(fn ($docs) => array_keys($docs))
            ->unique();

        foreach ($allDocumentKeys as $key) {
            $lead->documents()->updateOrCreate(
                ['key' => $key],
                ['checked' => (bool) ($submittedDocuments[$key] ?? false)],
            );
        }

        $lead->advanceStatusFromDocuments();

        $openBranches = array_filter(explode(',', (string) $request->input('open_branches')));

        return redirect()->route('leads.edit', [$lead, 'open' => $openBranches]);
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
