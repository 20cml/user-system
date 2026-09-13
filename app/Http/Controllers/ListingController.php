<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListingRequest;
use App\Models\Listing;
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
     * Show the form for adding a new listing.
     */
    public function create(): View
    {
        return view('listings.create');
    }

    /**
     * Create a new listing owned by the logged-in agent.
     */
    public function store(ListingRequest $request): RedirectResponse
    {
        $user = $request->user();

        $user->listings()->create([
            ...$request->safe()->except(['photo', 'remove_photo', 'status']),
            'status' => 'available',
            'source' => 'manual',
            'currency' => $user->country === 'CA' ? 'CAD' : 'USD',
            'photo_path' => $request->hasFile('photo') ? $request->file('photo')->store('listing-photos', 'public') : null,
        ]);

        return redirect()->route('listings.index');
    }

    /**
     * Show the form for editing an existing listing.
     */
    public function edit(Listing $listing): View
    {
        $this->authorize('update', $listing);

        return view('listings.edit', [
            'listing' => $listing,
        ]);
    }

    /**
     * Update an existing listing's details, status, and/or photo.
     */
    public function update(ListingRequest $request, Listing $listing): RedirectResponse
    {
        $this->authorize('update', $listing);

        $listing->fill($request->safe()->except(['photo', 'remove_photo']));

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

        return redirect()->route('listings.index');
    }

    /**
     * Permanently delete a listing.
     */
    public function destroy(Listing $listing): RedirectResponse
    {
        $this->authorize('delete', $listing);

        if ($listing->photo_path) {
            Storage::disk('public')->delete($listing->photo_path);
        }

        $listing->delete();

        return redirect()->route('listings.index');
    }
}
