<?php

declare(strict_types=1);

namespace Zandu\Tests\Platform\Auth\Security;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class PublicAuthRateLimitTest extends WebTestCase
{
    public function testRegistrationIsRateLimited(): void
    {
        $client = self::createClient(server: ['REMOTE_ADDR' => '2001:db8::' . dechex((int) getmypid())]);
        $client->disableReboot();

        for ($attempt = 0; $attempt < 5; ++$attempt) {
            $client->jsonRequest('POST', '/api/auth/register', []);
            self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $client->jsonRequest('POST', '/api/auth/register', []);

        self::assertResponseStatusCodeSame(Response::HTTP_TOO_MANY_REQUESTS);
        $payload = json_decode((string) $client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($payload);
        self::assertSame('RATE_LIMIT_EXCEEDED', $payload['code'] ?? null);
        self::assertTrue($client->getResponse()->headers->has('Retry-After'));
    }
}
