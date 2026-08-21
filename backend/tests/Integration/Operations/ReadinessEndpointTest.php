<?php

declare(strict_types=1);

namespace Zandu\Tests\Integration\Operations;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ReadinessEndpointTest extends WebTestCase
{
    public function testReadinessChecksPostgreSql(): void
    {
        if (!is_file('/.dockerenv')) {
            self::markTestSkipped('The PostgreSQL readiness probe runs inside Docker.');
        }

        $client = self::createClient();
        $client->request('GET', '/health/ready');

        self::assertResponseIsSuccessful();
        self::assertJsonStringEqualsJsonString(
            '{"status":"ready","database":"up"}',
            (string) $client->getResponse()->getContent(),
        );
    }
}
