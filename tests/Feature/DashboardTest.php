<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_groups_the_agents_own_leads_by_status(): void
    {
        $user = User::factory()->create();
        $otherAgent = User::factory()->create();

        Lead::factory()->for($user)->count(2)->create(['status' => 'new']);
        Lead::factory()->for($user)->create(['status' => 'qualified', 'first_name' => 'Qualified', 'last_name' => 'Lead']);
        Lead::factory()->for($otherAgent)->create(['status' => 'new']);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertViewHas('leadsByStatus', function ($leadsByStatus) {
            return $leadsByStatus['new']->count() === 2
                && $leadsByStatus['qualified']->count() === 1
                && $leadsByStatus['lost']->count() === 0;
        });
        $response->assertSee('Qualified Lead');
    }

    public function test_dashboard_shows_empty_columns_when_the_agent_has_no_leads(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertViewHas('leadsByStatus', function ($leadsByStatus) {
            return collect($leadsByStatus)->every(fn ($leads) => $leads->isEmpty());
        });
        $response->assertSee('No leads');
    }
}
