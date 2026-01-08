<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Flags\Controller\HealthController;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Response;

class HealthControllerTest extends KernelTestCase
{
    private Container $container;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->container = static::getContainer();
    }

    public function testHealthReturnsOk(): void
    {
        /** @var HealthController $controller */
        $controller = $this->container->get(HealthController::class);
        $controller->setContainer($this->container);

        $response = $controller->health();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertEquals('ok', $content['status']);
        $this->assertArrayHasKey('version', $content);
        $this->assertIsArray($content['version']);
        $this->assertArrayHasKey('environment', $content['version']);
    }

    public function testReadyReturnsOkWhenDatabaseIsConnected(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('executeQuery')->willReturn($this->createMock(\Doctrine\DBAL\Result::class));

        /** @var HealthController $controller */
        $controller = $this->container->get(HealthController::class);
        $controller->setContainer($this->container);

        $response = $controller->ready($connection);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertEquals('ok', $content['status']);
        $this->assertArrayHasKey('version', $content);
        $this->assertEquals('ok', $content['checks']['database']);
    }

    public function testReadyReturnsServiceUnavailableWhenDatabaseFails(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('executeQuery')->willThrowException(new \Exception('Connection failed'));

        /** @var HealthController $controller */
        $controller = $this->container->get(HealthController::class);
        $controller->setContainer($this->container);

        $response = $controller->ready($connection);

        $this->assertEquals(Response::HTTP_SERVICE_UNAVAILABLE, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertEquals('degraded', $content['status']);
        $this->assertArrayHasKey('version', $content);
        $this->assertEquals('error', $content['checks']['database']);
    }
}
