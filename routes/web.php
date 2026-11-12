<?php

use App\Funnel\Pipelines\PipelineResolver;
use App\Http\Controllers\Api\AddressSuggestionController;
use App\Http\Controllers\Api\LeadSearchController;
use App\Http\Controllers\Api\ListingSearchController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\ListingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function (Request $request) {
    $pipeline = in_array($request->query('pipeline'), PipelineResolver::LEAD_TYPES)
        ? $request->query('pipeline')
        : session('dashboard_pipeline', 'buyer');

    session(['dashboard_pipeline' => $pipeline]);

    $propertyType = in_array($request->query('property_type'), PipelineResolver::PROPERTY_TYPES)
        ? $request->query('property_type')
        : session('dashboard_property_type', 'condo');

    session(['dashboard_property_type' => $propertyType]);

    $pipelineStrategy = app(PipelineResolver::class)->forType(auth()->id(), $pipeline, $propertyType);
    $stages = $pipelineStrategy->stageKeys();
    $stageLabels = $pipelineStrategy->labels();

    $leads = auth()->user()->leads()->where('type', $pipeline)->where('property_type', $propertyType)->latest()->get()->groupBy('status');

    $leadsByStatus = collect($stages)->mapWithKeys(fn ($status) => [$status => $leads->get($status, collect())]);

    return view('dashboard', [
        'pipeline' => $pipeline,
        'propertyType' => $propertyType,
        'stageLabels' => $stageLabels,
        'leadsByStatus' => $leadsByStatus,
    ]);
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
    Route::redirect('/settings', '/settings/pipeline');
    Route::get('/settings/pipeline', [SettingsController::class, 'pipeline'])->name('settings.pipeline');
    Route::put('/settings/pipeline', [SettingsController::class, 'updatePipeline'])->name('settings.pipeline.update');
    Route::get('/settings/documents', [SettingsController::class, 'documents'])->name('settings.documents');
    Route::put('/settings/documents', [SettingsController::class, 'updateDocuments'])->name('settings.documents.update');

    Route::get('/listings', [ListingController::class, 'index'])->name('listings.index');
    Route::post('/listings', [ListingController::class, 'store'])->name('listings.store');
    Route::get('/listings/{listing}/edit', [ListingController::class, 'edit'])->name('listings.edit');
    Route::patch('/listings/{listing}', [ListingController::class, 'update'])->name('listings.update');
    Route::delete('/listings/{listing}', [ListingController::class, 'destroy'])->name('listings.destroy');
    Route::post('/listings/{listing}/notes', [ListingController::class, 'storeNote'])->name('listings.notes.store');
    Route::delete('/listings/{listing}/notes/{note}', [ListingController::class, 'destroyNote'])->name('listings.notes.destroy');

    Route::get('/leads', [LeadController::class, 'index'])->name('leads.index');
    Route::post('/leads', [LeadController::class, 'store'])->name('leads.store');
    Route::get('/leads/{lead}/edit', [LeadController::class, 'edit'])->name('leads.edit');
    Route::patch('/leads/{lead}', [LeadController::class, 'update'])->name('leads.update');
    Route::delete('/leads/{lead}', [LeadController::class, 'destroy'])->name('leads.destroy');
    Route::post('/leads/{lead}/notes', [LeadController::class, 'storeNote'])->name('leads.notes.store');
    Route::delete('/leads/{lead}/notes/{note}', [LeadController::class, 'destroyNote'])->name('leads.notes.destroy');
});

require __DIR__.'/auth.php';
