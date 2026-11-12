<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsDefaultFunnel;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;
    use SeedsDefaultFunnel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpSeedsDefaultFunnel();
    }

    public function test_dashboard_groups_the_agents_own_leads_by_status(): void
    {
        $user = User::factory()->create();
        $otherAgent = User::factory()->create();

        Lead::factory()->for($user)->count(2)->create(['property_type' => 'condo', 'type' => 'buyer', 'status' => 'new']);
        Lead::factory()->for($user)->create(['property_type' => 'condo', 'type' => 'buyer', 'status' => 'contacted', 'first_name' => 'Contacted', 'last_name' => 'Lead']);
        Lead::factory()->for($otherAgent)->create(['property_type' => 'condo', 'type' => 'buyer', 'status' => 'new']);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertViewHas('leadsByStatus', function ($leadsByStatus) {
            return $leadsByStatus['new']->count() === 2
                && $leadsByStatus['contacted']->count() === 1;
        });
        $response->assertSee('Contacted Lead');
    }

    public function test_dashboard_excludes_leads_outside_the_visible_pipeline_stages(): void
    {
        $user = User::factory()->create();
        Lead::factory()->for($user)->create(['property_type' => 'condo', 'type' => 'buyer', 'status' => 'qualified', 'first_name' => 'Qualified', 'last_name' => 'Lead']);
        Lead::factory()->for($user)->create(['property_type' => 'condo', 'type' => 'buyer', 'status' => 'a_removed_column', 'first_name' => 'Orphan', 'last_name' => 'Lead']);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertViewHas('leadsByStatus', function ($leadsByStatus) {
            return isset($leadsByStatus['qualified']) && ! isset($leadsByStatus['a_removed_column']);
        });
        $response->assertSee('Qualified Lead');
        $response->assertDontSee('Orphan Lead');
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

    public function test_dashboard_defaults_to_the_buyer_pipeline(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Buyer Pipeline');
        $response->assertSee('Qualified');
        $response->assertDontSee('Listed');
    }

    public function test_dashboard_can_switch_to_the_seller_pipeline(): void
    {
        $user = User::factory()->create();
        Lead::factory()->for($user)->create(['property_type' => 'condo', 'type' => 'buyer', 'status' => 'qualified', 'first_name' => 'Buyer', 'last_name' => 'Lead']);
        Lead::factory()->for($user)->create(['property_type' => 'condo', 'type' => 'seller', 'status' => 'listed', 'first_name' => 'Seller', 'last_name' => 'Lead']);

        $response = $this->actingAs($user)->get('/dashboard?pipeline=seller');

        $response->assertOk();
        $response->assertSee('Seller Pipeline');
        $response->assertSee('Listed');
        $response->assertSee('Offer Received');
        $response->assertDontSee('Qualified');
        $response->assertSee('Seller Lead');
        $response->assertDontSee('Buyer Lead');
    }

    public function test_dashboard_can_switch_to_the_renter_pipeline(): void
    {
        $user = User::factory()->create();
        Lead::factory()->for($user)->create(['property_type' => 'condo', 'type' => 'buyer', 'status' => 'qualified', 'first_name' => 'Buyer', 'last_name' => 'Lead']);
        Lead::factory()->for($user)->create(['property_type' => 'condo', 'type' => 'renter', 'status' => 'showing', 'first_name' => 'Renter', 'last_name' => 'Lead']);

        $response = $this->actingAs($user)->get('/dashboard?pipeline=renter');

        $response->assertOk();
        $response->assertSee('Renter Pipeline');
        $response->assertSee('Showing');
        $response->assertDontSee('Listed');
        $response->assertSee('Renter Lead');
        $response->assertDontSee('Buyer Lead');
    }

    public function test_dashboard_can_switch_to_the_landlord_pipeline(): void
    {
        $user = User::factory()->create();
        Lead::factory()->for($user)->create(['property_type' => 'condo', 'type' => 'buyer', 'status' => 'qualified', 'first_name' => 'Buyer', 'last_name' => 'Lead']);
        Lead::factory()->for($user)->create(['property_type' => 'condo', 'type' => 'landlord', 'status' => 'application_received', 'first_name' => 'Landlord', 'last_name' => 'Lead']);

        $response = $this->actingAs($user)->get('/dashboard?pipeline=landlord');

        $response->assertOk();
        $response->assertSee('Landlord Pipeline');
        $response->assertSee('Application Received');
        $response->assertSee('Rented');
        $response->assertDontSee('Qualified');
        $response->assertSee('Landlord Lead');
        $response->assertDontSee('Buyer Lead');
    }

    public function test_dashboard_remembers_the_last_chosen_pipeline_across_visits(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/dashboard?pipeline=seller');
        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Seller Pipeline');
    }

    public function test_dashboard_shows_the_columns_and_leads_of_the_chosen_property_type(): void
    {
        $user = User::factory()->create();
        \App\Models\PipelineStage::where(['user_id' => $user->id, 'lead_type' => 'buyer', 'property_type' => 'land'])->delete();
        \App\Models\PipelineStage::create(['user_id' => $user->id, 'lead_type' => 'buyer', 'property_type' => 'land', 'key' => 'zoning_check', 'label' => 'Zoning check', 'position' => 0]);
        app(\App\Funnel\Pipelines\PipelineResolver::class)->flush();

        Lead::factory()->for($user)->create(['type' => 'buyer', 'property_type' => 'condo', 'status' => 'new', 'first_name' => 'Condo', 'last_name' => 'Buyer']);
        Lead::factory()->for($user)->create(['type' => 'buyer', 'property_type' => 'land', 'status' => 'zoning_check', 'first_name' => 'Land', 'last_name' => 'Buyer']);

        $land = $this->actingAs($user)->get('/dashboard?pipeline=buyer&property_type=land');
        $land->assertOk()->assertSee('Zoning check')->assertSee('Land Buyer')->assertDontSee('Condo Buyer')->assertDontSee('Qualified');

        $condo = $this->actingAs($user)->get('/dashboard?pipeline=buyer&property_type=condo');
        $condo->assertOk()->assertSee('Condo Buyer')->assertDontSee('Land Buyer')->assertDontSee('Zoning check');
    }
}
