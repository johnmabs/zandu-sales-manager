<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Catalog\Application;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\Catalog\Application\{TenantUnitOfMeasureLoader, UnitOfMeasureQueryService, UnitOfMeasureViewFactory};
use Zandu\Modules\Catalog\Domain\{UnitOfMeasure, UnitOfMeasureCode, UnitOfMeasureDimension, UnitOfMeasureName, UnitOfMeasurePrecision, UnitOfMeasureRepository, UnitOfMeasureStatus};
use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Access\{PermissionCode, ResourceScope};
use Zandu\SharedKernel\Context\{ActorContext, ActorType};
use Zandu\SharedKernel\Decimal\RoundingMode;
use Zandu\SharedKernel\Identity\{ActorId, OrganizationId, UnitOfMeasureId};
use Zandu\SharedKernel\Messaging\CorrelationId;

final class UnitOfMeasureQueryServiceTest extends TestCase
{
    public function testItListsAndReadsTenantUnitsWithCatalogPermission(): void
    {
        $uuids = new SymfonyUuidFactory();
        $organizationId = OrganizationId::fromString('0198d1b1-b2a4-7b6e-8e0e-608484906502', $uuids);
        $unitId = UnitOfMeasureId::fromString('0198d273-147c-72d5-b75a-a936797ff9c8', $uuids);
        $unit = UnitOfMeasure::reconstitute(
            $unitId,
            $organizationId,
            UnitOfMeasureCode::fromString('KG'),
            UnitOfMeasureName::fromString('Kilogramme'),
            UnitOfMeasureDimension::Mass,
            UnitOfMeasurePrecision::fromInt(3),
            RoundingMode::HalfUp,
            UnitOfMeasureStatus::Active,
            2,
        );
        $units = $this->createMock(UnitOfMeasureRepository::class);
        $units->expects(self::once())->method('findAll')->with($organizationId)->willReturn([$unit]);
        $units->expects(self::once())->method('get')->with($organizationId, $unitId)->willReturn($unit);
        $authorization = $this->createMock(AuthorizationService::class);
        $authorization->expects(self::exactly(2))->method('authorize')->with(
            self::isInstanceOf(ActorContext::class),
            PermissionCode::CatalogRead,
            self::callback(static fn(ResourceScope $scope): bool => $scope->organizationId->equals($organizationId) && null === $scope->storeId),
        );
        $queries = new UnitOfMeasureQueryService(
            $units,
            new TenantUnitOfMeasureLoader($units),
            $authorization,
            new UnitOfMeasureViewFactory(),
        );
        $actor = new ActorContext(
            ActorId::fromString('0198c728-8f2d-7f43-92d8-3f0c75b80186', $uuids),
            $organizationId,
            ActorType::User,
            CorrelationId::fromString('0198c729-19da-75be-b508-1a4b36cf8d7a', $uuids),
            new DateTimeImmutable('2026-09-16T12:00:00Z'),
        );

        self::assertSame('KG', $queries->list($actor)[0]->code);
        $view = $queries->get($unitId, $actor);
        self::assertSame('MASS', $view->dimension);
        self::assertSame('HALF_UP', $view->roundingMode);
        self::assertSame('ACTIVE', $view->status);
    }
}
