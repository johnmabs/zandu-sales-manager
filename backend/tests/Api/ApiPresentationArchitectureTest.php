<?php

declare(strict_types=1);

namespace Zandu\Tests\Api;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class ApiPresentationArchitectureTest extends TestCase
{
    public function testGlobalApiResourceDirectoryContainsNoPhpCode(): void
    {
        $files = glob(dirname(__DIR__, 2) . '/src/ApiResource/*.php');
        self::assertSame([], false === $files ? [] : $files);
    }

    public function testApiResourcesLiveInsideAModulePresentationLayer(): void
    {
        $modules = dirname(__DIR__, 2) . '/src/Modules';
        $misplaced = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($modules));

        foreach ($iterator as $file) {
            if (!$file instanceof SplFileInfo || !$file->isFile() || 'php' !== $file->getExtension()) {
                continue;
            }
            $contents = file_get_contents($file->getPathname());
            if (false !== $contents && str_contains($contents, '#[ApiResource') && !str_contains($file->getPathname(), '/Presentation/Api/')) {
                $misplaced[] = $file->getPathname();
            }
        }

        self::assertSame([], $misplaced, 'API resources must belong to a module Presentation/Api layer.');
    }
}
