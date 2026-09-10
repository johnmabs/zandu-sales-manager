<?php

declare(strict_types=1);

namespace Zandu\Tests\Platform\Auth\Security;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Zandu\Platform\Auth\Http\RefreshTokenCookie;

final class JwtAuthenticationTest extends WebTestCase
{
    private static int $clientSequence = 10;

    public function testValidLoginReturnsAUsableAccessToken(): void
    {
        $client = $this->createIsolatedClient();
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
        self::assertArrayNotHasKey('refreshToken', $payload);
        $refreshCookie = $client->getCookieJar()->get(RefreshTokenCookie::NAME);
        self::assertNotNull($refreshCookie);
        self::assertTrue($refreshCookie->isSecure());
        self::assertTrue($refreshCookie->isHttpOnly());
        self::assertSame('strict', $refreshCookie->getSameSite());

        $client->request('GET', '/api', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $payload['token'],
        ]);

        self::assertResponseIsSuccessful();
    }

    public function testInvalidLoginIsRejected(): void
    {
        $client = $this->createIsolatedClient();
        $client->jsonRequest('POST', '/api/auth/login', [
            'email' => 'admin@zandu.test',
            'password' => 'invalid-password',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testProtectedApiRejectsAnAnonymousRequest(): void
    {
        $client = $this->createIsolatedClient();
        $client->request('GET', '/api');

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testRefreshTokenIsRotatedAndReuseRevokesTheSession(): void
    {
        $client = $this->createIsolatedClient();
        $client->disableReboot();
        $this->login($client);
        $originalRefreshToken = $this->refreshCookieValue($client);

        $client->jsonRequest('POST', '/api/auth/refresh');

        self::assertResponseIsSuccessful();
        $rotated = $this->responsePayload($client);
        self::assertArrayNotHasKey('refreshToken', $rotated);
        $rotatedRefreshToken = $this->refreshCookieValue($client);
        self::assertNotSame($originalRefreshToken, $rotatedRefreshToken);
        self::assertCount(3, explode('.', $rotated['token']));

        $client->getCookieJar()->set(new Cookie(RefreshTokenCookie::NAME, $originalRefreshToken, null, '/', '', true));
        $client->jsonRequest('POST', '/api/auth/refresh');
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);

        $client->getCookieJar()->set(new Cookie(RefreshTokenCookie::NAME, $rotatedRefreshToken, null, '/', '', true));
        $client->jsonRequest('POST', '/api/auth/refresh');
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testLogoutRevokesTheRefreshSession(): void
    {
        $client = $this->createIsolatedClient();
        $client->disableReboot();
        $this->login($client);
        $refreshToken = $this->refreshCookieValue($client);

        $client->jsonRequest('POST', '/api/auth/logout');
        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
        self::assertNull($client->getCookieJar()->get(RefreshTokenCookie::NAME));

        $client->getCookieJar()->set(new Cookie(RefreshTokenCookie::NAME, $refreshToken, null, '/', '', true));
        $client->jsonRequest('POST', '/api/auth/refresh');
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

    private function refreshCookieValue(object $client): string
    {
        $cookie = $client->getCookieJar()->get(RefreshTokenCookie::NAME);
        self::assertNotNull($cookie);

        return $cookie->getValue();
    }

    private function createIsolatedClient(): object
    {
        return self::createClient(server: [
            'HTTPS' => 'on',
            'REMOTE_ADDR' => '192.0.2.' . ++self::$clientSequence,
        ]);
    }
}
