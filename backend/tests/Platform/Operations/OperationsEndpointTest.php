<?php

declare(strict_types=1);

namespace Zandu\Tests\Platform\Operations;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class OperationsEndpointTest extends WebTestCase
{
    public function testLivenessEndpointIsPublicAndHealthy(): void
    {
        $client = self::createClient();
        $client->request('GET', '/health/live');

        self::assertResponseIsSuccessful();
        self::assertJsonStringEqualsJsonString(
            '{"status":"ok"}',
            (string) $client->getResponse()->getContent(),
        );
    }

    public function testMetricsEndpointExposesTheRequiredSeries(): void
    {
        $client = self::createClient();
        $client->request('GET', '/metrics');

        self::assertResponseIsSuccessful();
        $body = (string) $client->getResponse()->getContent();

        foreach ([
            'outbox_pending_count',
            'outbox_oldest_pending_age',
            'outbox_publish_failures',
            'worker_retry_count',
            'dead_letter_count',
        ] as $metric) {
            self::assertStringContainsString($metric, $body);
        }
    }
}
