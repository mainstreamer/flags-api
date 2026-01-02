<?php

declare(strict_types=1);

namespace App\Tests\Functional\Health;

use App\Tests\Functional\ApiTestCase;

final class RobotsTxtTest extends ApiTestCase
{
    public function testRobotsTxtReturnsOk(): void
    {
        $response = $this->api->get('/robots.txt');

        $response->assertOk();
        self::assertStringContainsString('User-agent: *', $response->getResponse()->getContent());
        self::assertStringContainsString('Disallow: /', $response->getResponse()->getContent());
    }
}
