<?php

declare(strict_types=1);

namespace Zandu\Modules\Operations\Infrastructure\Http;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final readonly class HealthController
{
    #[Route('/health/live', name: 'health_live', methods: ['GET'])]
    public function live(): JsonResponse
    {
        return new JsonResponse(['status' => 'ok']);
    }

    #[Route('/health/ready', name: 'health_ready', methods: ['GET'])]
    public function ready(Connection $connection): JsonResponse
    {
        try {
            $connection->fetchOne('SELECT 1');

            return new JsonResponse(['status' => 'ready', 'database' => 'up']);
        } catch (\Throwable) {
            return new JsonResponse(
                ['status' => 'not_ready', 'database' => 'down'],
                Response::HTTP_SERVICE_UNAVAILABLE,
            );
        }
    }
}
