<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListingRequest;
use App\Models\Lead;
use App\Models\Listing;
use App\Models\ListingNote;
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
     * Create a new listing owned by the logged-in agent. Its seller (for a
     * 'sale' listing) or landlord (for a 'rent' one) is an existing lead,
     * picked in the form.
     */
    public function store(ListingRequest $request): RedirectResponse
    {
        $user = $request->user();

        $listing = $user->listings()->create([
            ...$request->safe()->except(['photos', 'remove_photos', 'lead_ids']),
            'source' => 'manual',
            'currency' => $user->country === 'CA' ? 'CAD' : 'USD',
        ]);

        $this->savePhotos($request, $listing);

        $listing->leads()->sync($request->safe()->input('lead_ids', []));

        // Only the basics are asked for up front; land on the details to fill in the rest.
        return redirect()->route('listings.edit', [$listing, 'open' => ['details']]);
    }

    /**
     * Show the form for editing an existing listing.
     */
    public function edit(Listing $listing): View
    {
        $this->authorize('update', $listing);

        return view('listings.edit', [
            'listing' => $listing->load('leads', 'owner', 'buyer'),
            'owners' => $listing->user->leads()
                ->where('type', $listing->listing_type === 'rent' ? 'landlord' : 'seller')
                ->orderBy('first_name')->orderBy('last_name')
                ->get(),
        ]);
    }

    /**
     * Update an existing listing's details, status, photo, and/or linked leads.
     */
    public function update(ListingRequest $request, Listing $listing): RedirectResponse
    {
        $this->authorize('update', $listing);

        $listing->fill($request->safe()->except(['photos', 'remove_photos', 'lead_ids']));

        $listing->save();

        $this->savePhotos($request, $listing);

        $listing->leads()->sync($request->safe()->input('lead_ids', []));

        $openBranches = array_filter(explode(',', (string) $request->input('open_branches')));

        return redirect()->route('listings.edit', [$listing, 'open' => $openBranches]);
    }

    /**
     * Apply the photo changes in the request: delete the ones marked for
     * removal, store the new uploads after the existing ones, and keep the
     * listing's cover photo (its first) up to date. The 6-photo limit is
     * checked in ListingRequest.
     */
    private function savePhotos(ListingRequest $request, Listing $listing): void
    {
        $removeIds = (array) $request->input('remove_photos', []);
        $uploads = (array) $request->file('photos', []);

        if ($removeIds === [] && $uploads === []) {
            return;
        }

        $listing->photos()->whereIn('id', $removeIds)->get()->each(function ($photo) {
            Storage::disk('public')->delete($photo->path);
            $photo->delete();
        });

        $position = (int) $listing->photos()->max('position');

        foreach ($uploads as $upload) {
            $listing->photos()->create([
                'path' => $upload->store('listing-photos', 'public'),
                'position' => ++$position,
            ]);
        }

        $listing->syncCoverPhoto();
    }

    /**
     * Permanently delete a listing.
     */
    public function destroy(Listing $listing): RedirectResponse
    {
        $this->authorize('delete', $listing);

        $listing->deleteWithPhoto();

        return redirect()->route('listings.index');
    }

    /**
     * Add a timestamped note to a listing.
     */
    public function storeNote(Request $request, Listing $listing): RedirectResponse
    {
        $this->authorize('update', $listing);

        $request->validate(['body' => ['required', 'string']]);

        $listing->notes()->create(['body' => $request->input('body')]);

        return redirect()->route('listings.edit', $listing);
    }

    /**
     * Permanently delete a note from a listing.
     */
    public function destroyNote(Listing $listing, ListingNote $note): RedirectResponse
    {
        $this->authorize('update', $listing);

        abort_unless($note->listing_id === $listing->id, 404);

        $note->delete();

        return redirect()->route('listings.edit', $listing);
    }
}
