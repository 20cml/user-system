<?php

use App\Http\Controllers\Api\AddressSuggestionController;
use App\Http\Controllers\Api\LeadSearchController;
use App\Http\Controllers\Api\ListingSearchController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\ListingController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    $statuses = ['new', 'contacted', 'qualified', 'offer', 'under_contract', 'closed'];

    $leads = auth()->user()->leads()->latest()->get()->groupBy('status');

    $leadsByStatus = collect($statuses)->mapWithKeys(fn ($status) => [$status => $leads->get($status, collect())]);

    return view('dashboard', ['leadsByStatus' => $leadsByStatus]);
})->middleware(['auth', 'verified', 'profile.complete', 'no-cache'])->name('dashboard');

Route::middleware(['auth', 'no-cache'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/api/address-suggestions', AddressSuggestionController::class)->name('address-suggestions');
    Route::get('/api/listings-search', ListingSearchController::class)->name('listings.search');
    Route::get('/api/leads-search', LeadSearchController::class)->name('leads.search');
});

Route::middleware(['auth', 'verified', 'profile.complete', 'no-cache'])->group(function () {
    Route::get('/listings', [ListingController::class, 'index'])->name('listings.index');
    Route::post('/listings', [ListingController::class, 'store'])->name('listings.store');
    Route::get('/listings/{listing}/edit', [ListingController::class, 'edit'])->name('listings.edit');
    Route::patch('/listings/{listing}', [ListingController::class, 'update'])->name('listings.update');
    Route::delete('/listings/{listing}', [ListingController::class, 'destroy'])->name('listings.destroy');

    Route::get('/leads', [LeadController::class, 'index'])->name('leads.index');
    Route::post('/leads', [LeadController::class, 'store'])->name('leads.store');
    Route::get('/leads/{lead}/edit', [LeadController::class, 'edit'])->name('leads.edit');
    Route::patch('/leads/{lead}', [LeadController::class, 'update'])->name('leads.update');
    Route::delete('/leads/{lead}', [LeadController::class, 'destroy'])->name('leads.destroy');
    Route::post('/leads/{lead}/notes', [LeadController::class, 'storeNote'])->name('leads.notes.store');
    Route::delete('/leads/{lead}/notes/{note}', [LeadController::class, 'destroyNote'])->name('leads.notes.destroy');
});

require __DIR__.'/auth.php';
