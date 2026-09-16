<?php

declare(strict_types=1);

namespace Zandu\Tests\Architecture;

use FilesystemIterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Zandu\SharedKernel\Versioning\VersionedAggregate;

final class AggregateVersioningArchitectureTest extends TestCase
{
    /** @return iterable<string, array{class-string}> */
    public static function versionedDomainModels(): iterable
    {
        $modules = dirname(__DIR__, 2) . '/src/Modules';
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($modules, FilesystemIterator::SKIP_DOTS),
        );

        foreach ($files as $file) {
            if (!$file instanceof SplFileInfo || 'php' !== $file->getExtension() || !str_contains($file->getPathname(), '/Domain/')) {
                continue;
            }

            $source = file_get_contents($file->getPathname());
            if (false === $source || 1 !== preg_match('/private\s+int\s+\$version(?:\s*=\s*\d+)?/', $source)) {
                continue;
            }
            preg_match('/namespace\s+([^;]+);/', $source, $namespace);
            preg_match('/final\s+class\s+(\w+)/', $source, $class);
            if (!isset($namespace[1], $class[1])) {
                continue;
            }

            $className = $namespace[1] . '\\' . $class[1];
            yield $className => [$className];
        }
    }

    /** @param class-string $className */
    #[DataProvider('versionedDomainModels')]
    public function testEveryVersionedDomainModelUsesTheSharedMechanism(string $className): void
    {
        self::assertTrue(is_subclass_of($className, VersionedAggregate::class));

        $source = file_get_contents((new \ReflectionClass($className))->getFileName());
        self::assertIsString($source);
        self::assertStringNotContainsString('++$this->version', $source);
        self::assertStringContainsString('use TracksAggregateVersion;', $source);
        self::assertMatchesRegularExpression(
            '/private function __construct[\\s\\S]*?\\)\\s*\\{\\s*\\$this->assertValidVersion\\(\\);/',
            $source,
        );
    }
}
