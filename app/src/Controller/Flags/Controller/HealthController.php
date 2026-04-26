<?php

declare(strict_types=1);

namespace App\Flags\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class HealthController extends AbstractController
{
    #[Route('/health', name: 'health_check', methods: ['GET'])]
    public function health(): JsonResponse
    {
        return new JsonResponse([
            'status' => 'ok',
            'version' => $this->getVersion(),
        ], Response::HTTP_OK);
    }

    #[Route('/health/ready', name: 'health_ready', methods: ['GET'])]
    public function ready(Connection $connection): JsonResponse
    {
        try {
            $connection->executeQuery('SELECT 1');
            $dbStatus = 'ok';
        } catch (\Throwable) {
            $dbStatus = 'error';
        }

        $status = 'ok' === $dbStatus ? Response::HTTP_OK : Response::HTTP_SERVICE_UNAVAILABLE;

        return new JsonResponse([
            'status' => 'ok' === $dbStatus ? 'ok' : 'degraded',
            'version' => $this->getVersion(),
            'checks' => [
                'database' => $dbStatus,
            ],
        ], $status);
    }

    private function getVersion(): array
    {
        return [
            'version' => $this->getParameter('app.version'),
            'environment' => $this->getParameter('kernel.environment'),
        ];
    }

    #[Route('/robots.txt', name: 'robots_txt', methods: ['GET'])]
    public function robots(): Response
    {
        return new Response("User-agent: *\nDisallow: /\n", Response::HTTP_OK, [
            'Content-Type' => 'text/plain',
        ]);
    }
}
