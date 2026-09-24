<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListingRequest;
use App\Models\Listing;
use App\Models\Lead;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ListingController extends Controller
{
    use AuthorizesRequests;

    /**
     * List the logged-in agent's own listings, newest first, optionally filtered.
     */
    public function index(Request $request): View
    {
        $listings = $request->user()->listings()->latest();

        if ($request->filled('status')) {
            $listings->where('status', $request->query('status'));
        }

        if ($request->filled('listing_type')) {
            $listings->where('listing_type', $request->query('listing_type'));
        }

        if ($request->filled('property_type')) {
            $listings->where('property_type', $request->query('property_type'));
        }

        if ($request->filled('min_price')) {
            $listings->where('price', '>=', $request->query('min_price'));
        }

        if ($request->filled('max_price')) {
            $listings->where('price', '<=', $request->query('max_price'));
        }

        return view('listings.index', [
            'listings' => $listings->get(),
        ]);
    }

    /**
     * Create a new listing owned by the logged-in agent, along with the
     * lead selling or renting it out — a 'sale' listing gets a new Seller
     * lead, a 'rent' listing gets a new Landlord lead.
     */
    public function store(ListingRequest $request): RedirectResponse
    {
        $user = $request->user();

        $owner = $user->leads()->create([
            'first_name' => $request->safe()->input('owner_first_name'),
            'last_name' => $request->safe()->input('owner_last_name'),
            'phone' => $request->safe()->input('owner_phone'),
            'email' => $request->safe()->input('owner_email'),
            'type' => $request->input('listing_type') === 'rent' ? 'landlord' : 'seller',
            'property_type' => $request->input('property_type'),
            'status' => 'new',
        ]);

        $listing = $user->listings()->create([
            ...$request->safe()->except(['photo', 'remove_photo', 'status', 'lead_ids', 'owner_first_name', 'owner_last_name', 'owner_phone', 'owner_email']),
            'owner_lead_id' => $owner->id,
            'status' => 'available',
            'source' => 'manual',
            'currency' => $user->country === 'CA' ? 'CAD' : 'USD',
            'photo_path' => $request->hasFile('photo') ? $request->file('photo')->store('listing-photos', 'public') : null,
        ]);

        $listing->leads()->sync($request->safe()->input('lead_ids', []));

        return redirect()->route('listings.edit', $listing);
    }

    /**
     * Show the form for editing an existing listing.
     */
    public function edit(Listing $listing): View
    {
        $this->authorize('update', $listing);

        return view('listings.edit', [
            'listing' => $listing->load('leads', 'owner'),
        ]);
    }

    /**
     * Update an existing listing's details, status, photo, and/or linked leads.
     */
    public function update(ListingRequest $request, Listing $listing): RedirectResponse
    {
        $this->authorize('update', $listing);

        $listing->fill($request->safe()->except(['photo', 'remove_photo', 'lead_ids']));

        if ($request->boolean('remove_photo') && $listing->photo_path) {
            Storage::disk('public')->delete($listing->photo_path);
            $listing->photo_path = null;
        }

        if ($request->hasFile('photo')) {
            if ($listing->photo_path) {
                Storage::disk('public')->delete($listing->photo_path);
            }

            $listing->photo_path = $request->file('photo')->store('listing-photos', 'public');
        }

        $listing->save();

        $listing->leads()->sync($request->safe()->input('lead_ids', []));

        $openBranches = array_filter(explode(',', (string) $request->input('open_branches')));

        return redirect()->route('listings.edit', [$listing, 'open' => $openBranches]);
    }

    /**
     * Permanently delete a listing.
     */
    public function destroy(Listing $listing): RedirectResponse
    {
        $this->authorize('delete', $listing);

        $listing->deleteWithOwner();

        return redirect()->route('listings.index');
    }
}
