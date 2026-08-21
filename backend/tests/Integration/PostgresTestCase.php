<?php

declare(strict_types=1);

namespace Zandu\Tests\Integration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Tools\DsnParser;
use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\TestCase;

abstract class PostgresTestCase extends TestCase
{
    protected Connection $connection;

    protected function setUp(): void
    {
        parent::setUp();

        $url = $_ENV['DATABASE_URL'] ?? $_SERVER['DATABASE_URL'] ?? null;

        if (!is_string($url)) {
            self::markTestSkipped('DATABASE_URL is not available.');
        }

        $url = $this->testDatabaseUrl($url);
        $params = (new DsnParser(['postgresql' => 'pdo_pgsql']))->parse($url);
        $this->connection = DriverManager::getConnection($params);

        try {
            $this->connection->fetchOne('SELECT 1');
        } catch (\Throwable $exception) {
            if (str_contains($url, '@postgres:')) {
                throw $exception;
            }

            self::markTestSkipped('PostgreSQL integration database is not reachable from this environment.');
        }
    }

    protected function secondConnection(): Connection
    {
        return DriverManager::getConnection($this->connection->getParams());
    }

    private function testDatabaseUrl(string $url): string
    {
        $parts = parse_url($url);

        if (!isset($parts['path']) || str_ends_with($parts['path'], '_test')) {
            return $url;
        }

        $queryPosition = strpos($url, '?');
        $base = false === $queryPosition ? $url : substr($url, 0, $queryPosition);
        $query = false === $queryPosition ? '' : substr($url, $queryPosition);
        $databaseSeparator = strrpos($base, '/');

        if (false === $databaseSeparator) {
            return $url;
        }

        return substr($base, 0, $databaseSeparator).$parts['path'].'_test'.$query;
    }
}
