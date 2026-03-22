<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SecurityMiddlewareTest extends TestCase
{
    #[Test]
    public function normal_requests_are_allowed(): void
    {
        $response = $this->get('/health');

        $response->assertStatus(200);
    }

    #[Test]
    public function sql_injection_style_query_is_blocked(): void
    {
        $response = $this->get('/health?probe=1%20UNION%20SELECT%201');

        $response->assertStatus(403);
        $response->assertSee('Request blocked by security policy.');
    }

    #[Test]
    public function xss_style_query_is_blocked(): void
    {
        $response = $this->get('/health?name=%3Cscript%3Ealert(1)%3C%2Fscript%3E');

        $response->assertStatus(403);
        $response->assertSee('Request blocked by security policy.');
    }

    #[Test]
    public function missing_build_asset_returns_404_instead_of_dashboard_redirect(): void
    {
        $response = $this->get('/build/assets/does-not-exist.js');

        $response->assertStatus(404);
        $response->assertHeaderMissing('Location');
    }
}
