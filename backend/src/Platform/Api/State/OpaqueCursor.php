<?php

declare(strict_types=1);

namespace Zandu\Platform\Api\State;

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use JsonException;

final class OpaqueCursor
{
    public static function encode(string $id): string
    {
        try {
            $json = json_encode(['id' => $id], JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('Unable to encode the pagination cursor.', previous: $exception);
        }

        return rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
    }

    public static function decode(string $cursor): string
    {
        $padding = (4 - strlen($cursor) % 4) % 4;
        $json = base64_decode(strtr($cursor . str_repeat('=', $padding), '-_', '+/'), true);

        if (false === $json) {
            throw new InvalidArgumentException('The pagination cursor is invalid.');
        }

        try {
            $decoded = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('The pagination cursor is invalid.', previous: $exception);
        }

        if (!is_array($decoded) || 1 !== count($decoded) || !is_string($decoded['id'] ?? null) || '' === $decoded['id']) {
            throw new InvalidArgumentException('The pagination cursor is invalid.');
        }

        return $decoded['id'];
    }
}
