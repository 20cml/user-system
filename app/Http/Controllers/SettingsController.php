<?php

namespace App\Http\Controllers;

use App\Funnel\FunnelConfigurator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Settings: the agent's own funnel. "Pipeline columns" sets the columns of
 * the funnel of each lead type × property type, "Documents" sets what each
 * column asks for. Everything is saved through FunnelConfigurator.
 */
class SettingsController extends Controller
{
    public function pipeline(Request $request, FunnelConfigurator $configurator): View
    {
        $type = $this->leadType($request);
        $propertyType = $this->propertyType($request);

        return view('settings.pipeline', [
            'leadType' => $type,
            'propertyType' => $propertyType,
            'maxStages' => FunnelConfigurator::MAX_STAGES,
            'rows' => $configurator->stages($request->user(), $type, $propertyType)
                ->map(fn ($stage) => ['key' => $stage->key, 'label' => $stage->label])
                ->values(),
        ]);
    }

    public function updatePipeline(Request $request, FunnelConfigurator $configurator): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(FunnelConfigurator::LEAD_TYPES)],
            'property_type' => ['required', Rule::in(FunnelConfigurator::PROPERTY_TYPES)],
            'stages' => ['array', 'max:'.FunnelConfigurator::MAX_STAGES],
            'stages.*.key' => ['nullable', 'string', 'max:255'],
            'stages.*.label' => ['nullable', 'string', 'max:60'],
        ], [
            'stages.max' => __('A funnel can have at most :max columns.', ['max' => FunnelConfigurator::MAX_STAGES]),
        ]);

        $configurator->saveStages($request->user(), $data['type'], $data['property_type'], $data['stages'] ?? []);

        return redirect()->route('settings.pipeline', ['type' => $data['type'], 'property_type' => $data['property_type']]);
    }

    public function documents(Request $request, FunnelConfigurator $configurator): View
    {
        $type = $this->leadType($request);
        $propertyType = $this->propertyType($request);

        $documents = $configurator->documents($request->user(), $type, $propertyType);

        return view('settings.documents', [
            'leadType' => $type,
            'propertyType' => $propertyType,
            'maxDocuments' => FunnelConfigurator::MAX_DOCUMENTS_PER_STAGE,
            'stages' => $configurator->stages($request->user(), $type, $propertyType)->map(fn ($stage) => [
                'key' => $stage->key,
                'label' => $stage->label,
                'rows' => $documents[$stage->key]->map(fn ($doc) => ['key' => $doc->key, 'label' => $doc->label])->values(),
            ])->values(),
        ]);
    }

    public function updateDocuments(Request $request, FunnelConfigurator $configurator): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(FunnelConfigurator::LEAD_TYPES)],
            'property_type' => ['required', Rule::in(FunnelConfigurator::PROPERTY_TYPES)],
            'documents' => ['array'],
            'documents.*' => ['array', 'max:'.FunnelConfigurator::MAX_DOCUMENTS_PER_STAGE],
            'documents.*.*.key' => ['nullable', 'string', 'max:255'],
            'documents.*.*.label' => ['nullable', 'string', 'max:120'],
        ], [
            'documents.*.max' => __('A column can ask for at most :max documents.', ['max' => FunnelConfigurator::MAX_DOCUMENTS_PER_STAGE]),
        ]);

        $configurator->saveDocuments($request->user(), $data['type'], $data['property_type'], $data['documents'] ?? []);

        return redirect()->route('settings.documents', ['type' => $data['type'], 'property_type' => $data['property_type']]);
    }

    private function leadType(Request $request): string
    {
        return in_array($request->query('type'), FunnelConfigurator::LEAD_TYPES, true) ? $request->query('type') : 'buyer';
    }

    private function propertyType(Request $request): string
    {
        return in_array($request->query('property_type'), FunnelConfigurator::PROPERTY_TYPES, true) ? $request->query('property_type') : 'condo';
    }
}
