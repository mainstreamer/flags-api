<?php

declare(strict_types=1);

namespace App\Tests\Functional\Health;

use App\Tests\Functional\ApiTestCase;

final class HealthEndpointTest extends ApiTestCase
{
    public function test_health_returns_ok(): void
    {
        $this->api->get('/health')
            ->assertOk()
            ->assertJsonPath('status', 'ok');
    }

    public function test_ready_returns_database_status(): void
    {
        $this->api->get('/health/ready')
            ->assertOk()
            ->assertJsonStructure(['status', 'checks' => ['database']]);
    }
}
