<?php

declare(strict_types=1);

namespace Zandu\Tests\Integration\Persistence;

use Doctrine\DBAL\Types\StringType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Throwable;

final class DoctrinePostgreSqlTypeMappingTest extends KernelTestCase
{
    public function testMigrationOwnershipExcludesOrmMetadataFromSchemaGeneration(): void
    {
        self::bootKernel();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        try {
            $visibleTables = $entityManager->getConnection()->createSchemaManager()->listTableNames();
        } catch (Throwable $exception) {
            if (is_file('/.dockerenv')) {
                throw $exception;
            }

            self::markTestSkipped('PostgreSQL integration database is not reachable from this environment.');
        }

        $mappedClasses = array_map(
            static fn(ClassMetadata $metadata): string => $metadata->name,
            $entityManager->getMetadataFactory()->getAllMetadata(),
        );
        $ignoredClasses = $entityManager->getConfiguration()->getSchemaIgnoreClasses();

        sort($mappedClasses);
        sort($ignoredClasses);

        self::assertSame(['doctrine_migration_versions'], $visibleTables);
        self::assertSame($mappedClasses, $ignoredClasses);
    }

    public function testUuidArraysCanBeIntrospectedByDoctrineDbal(): void
    {
        self::bootKernel();
        $connection = self::getContainer()->get(EntityManagerInterface::class)->getConnection();

        try {
            $columns = $connection->createSchemaManager()->listTableColumns('inventory.stock_count');
        } catch (Throwable $exception) {
            if (is_file('/.dockerenv')) {
                throw $exception;
            }

            self::markTestSkipped('PostgreSQL integration database is not reachable from this environment.');
        }

        self::assertArrayHasKey('requested_product_ids', $columns);
        self::assertInstanceOf(StringType::class, $columns['requested_product_ids']->getType());
    }
}
