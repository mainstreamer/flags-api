<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Flags\Controller\HealthController;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class HealthControllerTest extends TestCase
{
    public function testHealthReturnsOk(): void
    {
        $controller = new HealthController();

        $response = $controller->health();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals('{"status":"ok"}', $response->getContent());
    }

    public function testReadyReturnsOkWhenDatabaseIsConnected(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('executeQuery')->willReturn($this->createMock(\Doctrine\DBAL\Result::class));

        $controller = new HealthController();

        $response = $controller->ready($connection);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertEquals('ok', $content['status']);
        $this->assertEquals('ok', $content['checks']['database']);
    }

    public function testReadyReturnsServiceUnavailableWhenDatabaseFails(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('executeQuery')->willThrowException(new \Exception('Connection failed'));

        $controller = new HealthController();

        $response = $controller->ready($connection);

        $this->assertEquals(Response::HTTP_SERVICE_UNAVAILABLE, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertEquals('degraded', $content['status']);
        $this->assertEquals('error', $content['checks']['database']);
    }
}
