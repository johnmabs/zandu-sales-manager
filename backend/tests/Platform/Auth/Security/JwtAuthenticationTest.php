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
        self::assertArrayHasKey('refreshToken', $payload);
        self::assertIsString($payload['refreshToken']);

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

    public function testRefreshTokenIsRotatedAndReuseRevokesTheSession(): void
    {
        $client = self::createClient();
        $client->disableReboot();
        $login = $this->login($client);
        $originalRefreshToken = $login['refreshToken'];

        $client->jsonRequest('POST', '/api/auth/refresh', ['refreshToken' => $originalRefreshToken]);

        self::assertResponseIsSuccessful();
        $rotated = $this->responsePayload($client);
        self::assertNotSame($originalRefreshToken, $rotated['refreshToken']);
        self::assertCount(3, explode('.', $rotated['token']));

        $client->jsonRequest('POST', '/api/auth/refresh', ['refreshToken' => $originalRefreshToken]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);

        $client->jsonRequest('POST', '/api/auth/refresh', ['refreshToken' => $rotated['refreshToken']]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testLogoutRevokesTheRefreshSession(): void
    {
        $client = self::createClient();
        $client->disableReboot();
        $refreshToken = $this->login($client)['refreshToken'];

        $client->jsonRequest('POST', '/api/auth/logout', ['refreshToken' => $refreshToken]);
        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        $client->jsonRequest('POST', '/api/auth/refresh', ['refreshToken' => $refreshToken]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @return array<string,mixed>
     */
    private function login(object $client): array
    {
        $client->jsonRequest('POST', '/api/auth/login', [
            'email' => 'admin@zandu.test',
            'password' => 'zandu-test-password',
        ]);
        self::assertResponseIsSuccessful();

        return $this->responsePayload($client);
    }

    /**
     * @return array<string,mixed>
     */
    private function responsePayload(object $client): array
    {
        $payload = json_decode((string) $client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($payload);

        return $payload;
    }
}
