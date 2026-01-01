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
        return new JsonResponse(['status' => 'ok'], Response::HTTP_OK);
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

        $status = $dbStatus === 'ok' ? Response::HTTP_OK : Response::HTTP_SERVICE_UNAVAILABLE;

        return new JsonResponse([
            'status' => $dbStatus === 'ok' ? 'ok' : 'degraded',
            'checks' => [
                'database' => $dbStatus,
            ],
        ], $status);
    }
}
