<?php

declare(strict_types=1);

namespace Zandu\Platform\Auth\Http;

use DateTimeImmutable;
use Symfony\Component\HttpFoundation\Cookie;

final class RefreshTokenCookie
{
    public const string NAME = '__Host-zandu_refresh';

    public function create(string $token, DateTimeImmutable $expiresAt): Cookie
    {
        return Cookie::create(self::NAME)
            ->withValue($token)
            ->withExpires($expiresAt)
            ->withPath('/')
            ->withSecure(true)
            ->withHttpOnly(true)
            ->withSameSite(Cookie::SAMESITE_STRICT);
    }

    public function clear(): Cookie
    {
        return Cookie::create(self::NAME)
            ->withValue('')
            ->withExpires(1)
            ->withPath('/')
            ->withSecure(true)
            ->withHttpOnly(true)
            ->withSameSite(Cookie::SAMESITE_STRICT);
    }
}
