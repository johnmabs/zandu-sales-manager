<?php

declare(strict_types=1);

namespace Zandu\Tests\Platform\Auth\Security;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class JwtAuthenticationTest extends WebTestCase
{
    public function testValidLoginReturnsAUsableAccessToken(): void
    {
        $client = self::createClient();
        $client->jsonRequest('POST', '/api/auth/login', [
            'email' => 'admin@zandu.test',
            'password' => 'zandu-test-password',
        ]);

        self::assertResponseIsSuccessful();
        $payload = json_decode((string) $client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($payload);
        self::assertArrayHasKey('token', $payload);
        self::assertIsString($payload['token']);
        self::assertCount(3, explode('.', $payload['token']));

        $client->request('GET', '/api', server: [
            'HTTP_AUTHORIZATION' => 'Bearer '.$payload['token'],
        ]);

        self::assertResponseIsSuccessful();
    }

    public function testInvalidLoginIsRejected(): void
    {
        $client = self::createClient();
        $client->jsonRequest('POST', '/api/auth/login', [
            'email' => 'admin@zandu.test',
            'password' => 'invalid-password',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testProtectedApiRejectsAnAnonymousRequest(): void
    {
        $client = self::createClient();
        $client->request('GET', '/api');

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }
}
