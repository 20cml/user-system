<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreventBrowserCachingTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_pages_are_never_cached_by_the_browser(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/listings');

        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('no-cache', $cacheControl);
        $this->assertStringContainsString('must-revalidate', $cacheControl);
    }
}
