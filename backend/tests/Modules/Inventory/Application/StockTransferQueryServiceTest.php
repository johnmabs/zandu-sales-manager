<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Inventory\Application;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Inventory\Application\{StockTransferQueryService, StockTransferViewFactory};
use Zandu\Modules\Inventory\Domain\StockTransfer\{StockTransfer, StockTransferRepository};
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Access\{AuthorizationDenied, PermissionCode, ResourceScope};
use Zandu\SharedKernel\Context\{ActorContext, ActorType};
use Zandu\SharedKernel\Identity\{ActorId, OrganizationId, StockTransferId, StoreId};
use Zandu\SharedKernel\Messaging\CorrelationId;

final class StockTransferQueryServiceTest extends TestCase
{
    public function testCollectionKeepsTransfersReadableFromEitherStoreAndHidesTheOthers(): void
    {
        $ids = new SymfonyUuidFactory();
        $organizationId = OrganizationId::fromString('019a0300-0000-7000-8000-000000000001', $ids);
        $actor = new ActorContext(
            ActorId::fromString('019a0300-0000-7000-8000-000000000002', $ids),
            $organizationId,
            ActorType::User,
            CorrelationId::fromString('019a0300-0000-7000-8000-000000000003', $ids),
            new DateTimeImmutable('2026-09-01T08:00:00Z'),
        );
        $readableDestination = StoreId::fromString('019a0300-0000-7000-8000-000000000005', $ids);
        $visible = $this->transfer($ids, $organizationId, '10', '4', '5');
        $hidden = $this->transfer($ids, $organizationId, '20', '6', '7');
        $repository = $this->createStub(StockTransferRepository::class);
        $repository->method('findAll')->willReturn([$visible, $hidden]);
        $authorization = $this->createStub(AuthorizationService::class);
        $authorization->method('authorize')->willReturnCallback(static function (ActorContext $context, PermissionCode $permission, ResourceScope $scope) use ($readableDestination): void {
            if (!$scope->storeId?->equals($readableDestination)) {
                throw AuthorizationDenied::forPermission($context, $permission, $scope);
            }
        });

        $views = (new StockTransferQueryService($repository, $authorization, new StockTransferViewFactory()))->list($actor);

        self::assertCount(1, $views);
        self::assertSame($visible->id()->toString(), $views[0]->id);
    }

    private function transfer(SymfonyUuidFactory $ids, OrganizationId $organizationId, string $suffix, string $sourceSuffix, string $destinationSuffix): StockTransfer
    {
        return StockTransfer::create(
            StockTransferId::fromString(sprintf('019a0300-0000-70%s-8000-0000000000%s', $suffix, $suffix), $ids),
            $organizationId,
            StoreId::fromString(sprintf('019a0300-0000-7000-8000-00000000000%s', $sourceSuffix), $ids),
            StoreId::fromString(sprintf('019a0300-0000-7000-8000-00000000000%s', $destinationSuffix), $ids),
            ActorId::fromString('019a0300-0000-7000-8000-000000000002', $ids),
            new DateTimeImmutable('2026-09-01T08:00:00Z'),
        );
    }
}
