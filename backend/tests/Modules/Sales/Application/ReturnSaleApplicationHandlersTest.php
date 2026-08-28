<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Sales\Application;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\Modules\Sales\Application\{AddReturnSaleLine, AddReturnSaleLineHandler, CancelReturnSale, CancelReturnSaleHandler, CreateReturnSale, CreateReturnSaleHandler, ReturnSaleEventPublisher};
use Zandu\Modules\Sales\Domain\{ReturnSale, ReturnSaleRepository, ReturnSaleStatus, Sale, SaleLine, SaleLineCostSnapshotRepository, SaleRepository};
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\Platform\Identity\{SymfonyUuidFactory, SymfonyUuidV7Generator};
use Zandu\SharedKernel\Access\{AuthorizationDenied, PermissionCode, ResourceScope};
use Zandu\SharedKernel\Context\{ActorContext, ActorType};
use Zandu\SharedKernel\Identity\{ActorId, OrganizationId, ProductId, ProductPackagingId, ReturnSaleId, SaleId, SaleLineId, StoreId, UnitOfMeasureId};
use Zandu\SharedKernel\Messaging\{CorrelationId, OutboxRepository};
use Zandu\SharedKernel\Money\{Currency, Money};
use Zandu\SharedKernel\Quantity\Quantity;
use Zandu\SharedKernel\SecurityAudit\SecurityAuditTrail;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\Tests\SharedKernel\Time\FrozenClock;

final class ReturnSaleApplicationHandlersTest extends TestCase
{
    public function testItCreatesAReturnForACompletedSale(): void
    {
        $sale = $this->completedSale();
        $returns = $this->createMock(ReturnSaleRepository::class);
        $returns->expects(self::once())->method('save');
        $handler = new CreateReturnSaleHandler(
            $this->sales($sale),
            $returns,
            $this->transaction(),
            $this->createStub(AuthorizationService::class),
            $this->createStub(OperationalGuard::class),
            $this->createStub(SecurityAuditTrail::class),
            $this->events(),
            new SymfonyUuidV7Generator(),
            $this->clock(),
        );

        $return = $handler(new CreateReturnSale($sale->id(), ' Damaged ', $this->actor()));

        self::assertSame(ReturnSaleStatus::Draft, $return->status());
        self::assertSame('Damaged', $return->reason());
        self::assertTrue($sale->id()->equals($return->saleId()));
    }

    public function testStoreScopedDenialPreventsReturnCreation(): void
    {
        $sale = $this->completedSale();
        $actor = $this->actor();
        $scope = ResourceScope::store($this->organizationId(), $this->storeId());
        $authorization = $this->createMock(AuthorizationService::class);
        $authorization->expects(self::once())
            ->method('authorize')
            ->with($actor, PermissionCode::SaleReturnCreate, self::callback(
                fn(ResourceScope $actual): bool => $actual->organizationId->equals($scope->organizationId)
                    && $actual->storeId?->equals($this->storeId()),
            ))
            ->willThrowException(AuthorizationDenied::forPermission($actor, PermissionCode::SaleReturnCreate, $scope));
        $returns = $this->createMock(ReturnSaleRepository::class);
        $returns->expects(self::never())->method('save');
        $guard = $this->createMock(OperationalGuard::class);
        $guard->expects(self::never())->method('assertStore');
        $handler = new CreateReturnSaleHandler(
            $this->sales($sale),
            $returns,
            $this->transaction(),
            $authorization,
            $guard,
            $this->createStub(SecurityAuditTrail::class),
            $this->events(),
            new SymfonyUuidV7Generator(),
            $this->clock(),
        );

        $this->expectException(AuthorizationDenied::class);
        $handler(new CreateReturnSale($sale->id(), null, $actor));
    }

    public function testItAddsAnOriginalSaleLineToADraftReturn(): void
    {
        $sale = $this->completedSale();
        $return = ReturnSale::create($this->returnId(), $this->organizationId(), $this->storeId(), $sale->id(), null, $this->actor(), new DateTimeImmutable());
        $returns = $this->createMock(ReturnSaleRepository::class);
        $returns->method('getForUpdate')->willReturn($return);
        $returns->expects(self::once())->method('save');
        $costs = $this->createStub(SaleLineCostSnapshotRepository::class);
        $handler = new AddReturnSaleLineHandler(
            $returns,
            $this->sales($sale),
            $costs,
            $this->transaction(),
            $this->createStub(AuthorizationService::class),
            $this->createStub(OperationalGuard::class),
            $this->createStub(SecurityAuditTrail::class),
            $this->events(),
            new SymfonyUuidV7Generator(),
            $this->clock(),
        );

        $updated = $handler(new AddReturnSaleLine($return->id(), $sale->lines()[0]->id(), $this->quantity('1'), false, 'Opened', $this->actor()));

        self::assertSame(1, $updated->lineCount());
        self::assertSame('Opened', $updated->lines()[0]->reason());
        self::assertFalse($updated->lines()[0]->restock());
    }

    public function testItCancelsADraftReturn(): void
    {
        $sale = $this->completedSale();
        $return = ReturnSale::create($this->returnId(), $this->organizationId(), $this->storeId(), $sale->id(), null, $this->actor(), new DateTimeImmutable());
        $returns = $this->createMock(ReturnSaleRepository::class);
        $returns->method('getForUpdate')->willReturn($return);
        $returns->expects(self::once())->method('save');
        $handler = new CancelReturnSaleHandler($returns, $this->transaction(), $this->createStub(AuthorizationService::class), $this->createStub(OperationalGuard::class), $this->createStub(SecurityAuditTrail::class), $this->events(), $this->clock());

        $cancelled = $handler(new CancelReturnSale($return->id(), $this->actor()));

        self::assertSame(ReturnSaleStatus::Cancelled, $cancelled->status());
    }

    private function completedSale(): Sale
    {
        $currency = Currency::fromCode('XAF');
        $zero = Money::fromString('0', $currency, $this->decimals());
        $total = Money::fromString('1000', $currency, $this->decimals());
        $sale = Sale::create($this->saleId(), $this->organizationId(), $this->storeId(), 'XAF', $zero, $this->actor(), new DateTimeImmutable());
        $sale->addLine(new SaleLine(
            $this->saleLineId(),
            $sale->id(),
            ProductId::fromString('019a3800-0000-7000-8000-000000000006', $this->uuids()),
            ProductPackagingId::fromString('019a3800-0000-7000-8000-000000000007', $this->uuids()),
            'SKU',
            'Product',
            'EA',
            'Each',
            UnitOfMeasureId::fromString('019a3800-0000-7000-8000-000000000008', $this->uuids()),
            $this->quantity('2'),
            $this->quantity('1'),
            $this->quantity('2'),
            Money::fromString('500', $currency, $this->decimals()),
            null,
            null,
            $zero,
            $total,
            $zero,
            $total,
            $total,
        ));
        $sale->complete($this->actor(), new DateTimeImmutable(), '2026-08-28');

        return $sale;
    }

    private function sales(Sale $sale): SaleRepository
    {
        $repository = $this->createStub(SaleRepository::class);
        $repository->method('get')->willReturn($sale);

        return $repository;
    }

    private function transaction(): TenantTransaction
    {
        return new class implements TenantTransaction {
            public function transactional(OrganizationId $organizationId, callable $operation): mixed
            {
                return $operation();
            }
        };
    }

    private function events(): ReturnSaleEventPublisher
    {
        return new ReturnSaleEventPublisher($this->createStub(OutboxRepository::class), new SymfonyUuidV7Generator(), $this->clock());
    }

    private function clock(): FrozenClock
    {
        return new FrozenClock(new DateTimeImmutable('2026-08-28T12:00:00Z'));
    }
    private function actor(): ActorContext
    {
        return new ActorContext(ActorId::fromString('019a3800-0000-7000-8000-000000000005', $this->uuids()), $this->organizationId(), ActorType::User, CorrelationId::fromString('019a3800-0000-7000-8000-000000000009', $this->uuids()), new DateTimeImmutable());
    }
    private function organizationId(): OrganizationId
    {
        return OrganizationId::fromString('019a3800-0000-7000-8000-000000000001', $this->uuids());
    }
    private function storeId(): StoreId
    {
        return StoreId::fromString('019a3800-0000-7000-8000-000000000002', $this->uuids());
    }
    private function saleId(): SaleId
    {
        return SaleId::fromString('019a3800-0000-7000-8000-000000000003', $this->uuids());
    }
    private function saleLineId(): SaleLineId
    {
        return SaleLineId::fromString('019a3800-0000-7000-8000-000000000004', $this->uuids());
    }
    private function returnId(): ReturnSaleId
    {
        return ReturnSaleId::fromString('019a3800-0000-7000-8000-000000000010', $this->uuids());
    }
    private function quantity(string $value): Quantity
    {
        return Quantity::fromString($value, $this->decimals());
    }
    private function decimals(): BrickDecimalFactory
    {
        return new BrickDecimalFactory();
    }
    private function uuids(): SymfonyUuidFactory
    {
        return new SymfonyUuidFactory();
    }
}
