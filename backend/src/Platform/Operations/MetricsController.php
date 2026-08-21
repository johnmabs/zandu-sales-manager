<?php

declare(strict_types=1);

namespace Zandu\Platform\Operations;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final readonly class MetricsController
{
    public function __construct(private OperationalMetrics $metrics) {}

    #[Route('/metrics', name: 'operations_metrics', methods: ['GET'])]
    public function __invoke(): Response
    {
        $lines = [];

        foreach ($this->metrics->snapshot() as $name => $value) {
            $lines[] = '# TYPE ' . $name . ' gauge';
            $lines[] = $name . ' ' . $value;
        }

        return new Response(implode("\n", $lines) . "\n", headers: [
            'Content-Type' => 'text/plain; version=0.0.4; charset=utf-8',
        ]);
    }
}
