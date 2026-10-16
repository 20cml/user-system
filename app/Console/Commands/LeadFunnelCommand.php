<?php

namespace App\Console\Commands;

use App\Funnel\LeadFunnelService;
use App\Models\Lead;
use Illuminate\Console\Command;

/**
 * Second interface to the lead funnel, next to the web UI. It holds no
 * funnel rules of its own: it reads and writes through LeadFunnelService,
 * the same class the web controllers use.
 */
class LeadFunnelCommand extends Command
{
    protected $signature = 'lead:funnel
                            {lead : The lead ID}
                            {--check=* : Document key to mark as received (repeatable)}
                            {--uncheck=* : Document key to mark as not received (repeatable)}
                            {--lost : Mark the lead as Lost (automation will no longer move it)}';

    protected $description = 'Show a lead\'s funnel stage and checklist, optionally checking documents off';

    public function handle(LeadFunnelService $funnel): int
    {
        $lead = Lead::find($this->argument('lead'));

        if (! $lead) {
            $this->error("Lead #{$this->argument('lead')} not found.");

            return self::FAILURE;
        }

        if ($funnel->pipelineFor($lead) === null) {
            $this->error("Lead #{$lead->id} has no type, so it has no funnel.");

            return self::FAILURE;
        }

        $check = (array) $this->option('check');
        $uncheck = (array) $this->option('uncheck');
        $known = $lead->documentKeys();

        $unknown = array_diff([...$check, ...$uncheck], $known);

        if ($unknown) {
            $this->error('Unknown document key(s): '.implode(', ', $unknown));
            $this->line('Valid keys: '.implode(', ', $known));

            return self::FAILURE;
        }

        $before = $lead->status;

        if ($this->option('lost')) {
            $lead->update(['status' => 'lost']);
        }

        if ($check || $uncheck) {
            $current = $lead->documents()->pluck('checked', 'key')->all();

            foreach ($check as $key) {
                $current[$key] = true;
            }

            foreach ($uncheck as $key) {
                $current[$key] = false;
            }

            $funnel->syncDocuments($lead, $current);
            $lead->refresh();
        }

        $this->render($funnel->summary($lead), $before);

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $summary
     */
    private function render(array $summary, string $before): void
    {
        $this->info("Lead #{$summary['id']} — {$summary['name']} ({$summary['type']}, ".($summary['property_type'] ?? 'no property type').')');

        $moved = $before !== $summary['status'] ? "  (was: {$before})" : '';
        $this->line("Stage: <options=bold>{$summary['stage']}</>{$moved}");
        $this->line('Next:  '.($summary['next_stage'] ?? '—'));

        if ($summary['needs_listing_narrowed']) {
            $this->warn('Waiting: link exactly one listing to move forward.');
        }

        if ($summary['blocked_by_lead_id']) {
            $this->warn("Waiting: lead #{$summary['blocked_by_lead_id']} already has this listing under contract.");
        }

        $this->newLine();

        foreach ($summary['stages'] as $stage) {
            $marker = $stage['current'] ? '▶' : ' ';
            $state = $stage['complete'] ? 'done' : 'open';
            $this->line("{$marker} {$stage['label']} [{$state}]");

            foreach ($stage['documents'] as $document) {
                $box = $document['checked'] ? '[x]' : '[ ]';
                $this->line("      {$box} {$document['key']} — {$document['label']}");
            }
        }
    }
}
