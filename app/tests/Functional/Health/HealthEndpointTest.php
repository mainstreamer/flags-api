<?php

declare(strict_types=1);

namespace App\Tests\Functional\Health;

use App\Tests\Functional\ApiTestCase;

final class HealthEndpointTest extends ApiTestCase
{
    public function testHealthReturnsOk(): void
    {
        $this->api->get('/health')
            ->assertOk()
            ->assertJsonPath('status', 'ok');
    }

    public function testReadyReturnsDatabaseStatus(): void
    {
        $this->api->get('/health/ready')
            ->assertOk()
            ->assertJsonStructure(['status', 'checks' => ['database']]);
    }
}
