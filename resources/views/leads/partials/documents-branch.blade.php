{{-- Documents node. Expects `$lead` and an enclosing `documentsOpen` Alpine var.
     Pass `$form` (a form id) when these fields sit outside their `<form>` tag. --}}
@php $form = $form ?? null; @endphp
@if ($lead->hasDocumentChecklist())
    <div class="pl-6 -ml-px border-l-2 border-transparent py-3">
        <div class="flex items-start gap-6">
            <button type="button" @click="documentsOpen = !documentsOpen" class="shrink-0 w-36 flex items-center gap-2 text-[12.1px] font-medium" :class="documentsOpen ? 'text-gray-900' : 'text-gray-600 hover:text-gray-900'">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 00-1.883 2.542l.857 6a2.25 2.25 0 002.227 1.932H19.05a2.25 2.25 0 002.227-1.932l.857-6a2.25 2.25 0 00-1.883-2.542m-16.5 0V6A2.25 2.25 0 016 3.75h3.879a1.5 1.5 0 011.06.44l2.122 2.12a1.5 1.5 0 001.06.44H18A2.25 2.25 0 0120.25 9v.776" />
                </svg>
                {{ __('Documents') }}
                <svg class="h-3 w-3 transition-transform" :class="documentsOpen ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
            </button>

            @php
                $docStages = collect(\App\Models\Lead::PIPELINE_STAGES_BY_TYPE[$lead->type] ?? []);
                $docStageLabels = \App\Models\Lead::PIPELINE_STAGE_LABELS[$lead->type] ?? [];
                $docStageDefault = $lead->documents->contains('checked', true);
                $docStageInit = $docStages->mapWithKeys(fn ($stage) => [$stage => $docStageDefault])->all();
            @endphp
            <div x-show="documentsOpen" class="flex-1 max-w-sm border-l border-gray-100 pl-6" x-data='{ docStage: @json($docStageInit) }'>
                <div class="border-l-2 border-gray-200 pl-6">
                    @foreach ($docStages as $stageKey)
                        @php $stageLabel = $docStageLabels[$stageKey] ?? ucfirst($stageKey); @endphp
                        <div class="py-2">
                            <button type="button" @click="docStage.{{ $stageKey }} = !docStage.{{ $stageKey }}" class="w-full flex items-center gap-2 text-[12.1px] font-medium" :class="docStage.{{ $stageKey }} ? 'text-gray-900' : 'text-gray-600 hover:text-gray-900'">
                                {{ $stageLabel }}
                                <svg class="h-3 w-3 transition-transform" :class="docStage.{{ $stageKey }} ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>

                            <div x-show="docStage.{{ $stageKey }}" class="mt-2 border-l border-gray-100 pl-4 space-y-2">
                                @php $stageDocuments = $lead->documentChecklistForStage($stageKey); @endphp
                                @forelse ($stageDocuments as $key => $doc)
                                    <label class="flex items-center gap-2 text-[12.1px] text-gray-700">
                                        <input type="checkbox" @if ($form) form="{{ $form }}" @endif name="documents[{{ $key }}]" value="1" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" @checked($doc['checked'])>
                                        {{ __($doc['label']) }}
                                    </label>
                                @empty
                                    <p class="text-[12.1px] text-gray-400">—</p>
                                @endforelse
                            </div>
                        </div>
                    @endforeach

                    <div class="pt-2 pb-2">
                        <x-primary-button :form="$form">{{ __('Save') }}</x-primary-button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
