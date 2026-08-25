<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Pricing\Application;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\Modules\Pricing\Application\ActivatePriceList\ActivatePriceList;
use Zandu\Modules\Pricing\Application\ActivatePriceList\ActivatePriceListHandler;
use Zandu\Modules\Pricing\Application\UpdateProductPrice\UpdateProductPrice;
use Zandu\Modules\Pricing\Application\UpdateProductPrice\UpdateProductPriceHandler;
use Zandu\Modules\Pricing\Domain\PriceList\PriceList;
use Zandu\Modules\Pricing\Domain\PriceList\PriceListCode;
use Zandu\Modules\Pricing\Domain\PriceList\PriceListName;
use Zandu\Modules\Pricing\Domain\PriceList\PriceListPriority;
use Zandu\Modules\Pricing\Domain\PriceList\PriceListRepository;
use Zandu\Modules\Pricing\Domain\PriceList\PriceListStatus;
use Zandu\Modules\Pricing\Domain\ProductPrice\ProductPrice;
use Zandu\Modules\Pricing\Domain\ProductPrice\ProductPriceRepository;
use Zandu\Modules\Pricing\Domain\ProductPrice\ProductPriceTarget;
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Access\ResourceScope;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Context\ActorType;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\PriceListId;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\ProductPackagingId;
use Zandu\SharedKernel\Identity\ProductPriceId;
use Zandu\SharedKernel\Messaging\CorrelationId;
use Zandu\SharedKernel\Money\Currency;
use Zandu\SharedKernel\Money\Money;
use Zandu\SharedKernel\SecurityAudit\ResourceReference;
use Zandu\SharedKernel\SecurityAudit\SafeAuditMetadata;
use Zandu\SharedKernel\SecurityAudit\SecurityAction;
use Zandu\SharedKernel\SecurityAudit\SecurityAuditTrail;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\Tests\SharedKernel\Time\FrozenClock;

final class PricingAuditHandlerTest extends TestCase
{
    private const ORGANIZATION_ID = '0198d1b1-b2a4-7b6e-8e0e-608484906502';
    private const PRICE_LIST_ID = '0198d301-147c-72d5-b75a-a936797ff9c8';
    private const PRODUCT_PRICE_ID = '0198d302-147c-72d5-b75a-a936797ff9c8';
    private const PRODUCT_ID = '0198d303-147c-72d5-b75a-a936797ff9c8';
    private const PACKAGING_ID = '0198d304-147c-72d5-b75a-a936797ff9c8';
    private const ACTOR_ID = '0198c728-8f2d-7f43-92d8-3f0c75b80186';
    private const CORRELATION_ID = '0198c729-19da-75be-b508-1a4b36cf8d7a';

    public function testItActivatesAndAuditsAPriceListInsideTheTenantTransaction(): void
    {
        $context = $this->context();
        $priceList = $this->priceList();
        $repository = $this->createMock(PriceListRepository::class);
        $repository->expects(self::once())->method('get')->with($context->organizationId(), $priceList->id())->willReturn($priceList);
        $repository->expects(self::once())->method('save')->with($priceList);
        $authorization = $this->authorization($context, PermissionCode::PriceListActivate);
        $guard = $this->guard($context);
        $audit = $this->audit($context, SecurityAction::PriceListActivated, 'PRICE_LIST', self::PRICE_LIST_ID);

        $activated = (new ActivatePriceListHandler(
            $repository,
            $this->clock(),
            $this->transaction(),
            $authorization,
            $guard,
            $audit,
        ))(new ActivatePriceList($priceList->id(), $context));

        self::assertSame(PriceListStatus::Active, $activated->status());
        self::assertSame(2, $activated->version());
    }

    public function testItUpdatesAndAuditsAProductPriceInsideTheTenantTransaction(): void
    {
        $context = $this->context();
        $priceList = $this->priceList();
        $priceList->activate($context->actorId(), new DateTimeImmutable('2026-08-25T08:00:00Z'));
        $productPrice = $this->productPrice($priceList);
        $productPrices = $this->createMock(ProductPriceRepository::class);
        $productPrices->expects(self::once())->method('get')->with($context->organizationId(), $productPrice->id())->willReturn($productPrice);
        $productPrices->expects(self::once())->method('save')->with($productPrice);
        $priceLists = $this->createMock(PriceListRepository::class);
        $priceLists->expects(self::once())->method('get')->with($context->organizationId(), $priceList->id())->willReturn($priceList);
        $amount = Money::fromString('1250.50', Currency::fromCode('XOF'), new BrickDecimalFactory());

        $updated = (new UpdateProductPriceHandler(
            $productPrices,
            $priceLists,
            $this->clock(),
            $this->transaction(),
            $this->authorization($context, PermissionCode::ProductPriceUpdate),
            $this->guard($context),
            $this->audit($context, SecurityAction::ProductPriceUpdated, 'PRODUCT_PRICE', self::PRODUCT_PRICE_ID),
        ))(new UpdateProductPrice($productPrice->id(), $amount, null, null, $context));

        self::assertTrue($amount->equals($updated->amount()));
        self::assertSame(2, $updated->version());
    }

    private function authorization(ActorContext $context, PermissionCode $permission): AuthorizationService
    {
        $authorization = $this->createMock(AuthorizationService::class);
        $authorization->expects(self::once())->method('authorize')->with($context, $permission, self::callback(
            static fn(ResourceScope $scope): bool => $scope->organizationId->equals($context->organizationId()) && null === $scope->storeId,
        ));

        return $authorization;
    }

    private function guard(ActorContext $context): OperationalGuard
    {
        $guard = $this->createMock(OperationalGuard::class);
        $guard->expects(self::once())->method('assertTenant')->with($context);
        $guard->expects(self::never())->method('assertStore');

        return $guard;
    }

    private function audit(ActorContext $context, SecurityAction $action, string $type, string $id): SecurityAuditTrail
    {
        $audit = $this->createMock(SecurityAuditTrail::class);
        $audit->expects(self::once())->method('recordSuccess')->with(
            $context,
            $action,
            self::callback(static fn(ResourceReference $target): bool => $type === $target->type && $id === $target->id),
            self::isInstanceOf(SafeAuditMetadata::class),
            $this->clock()->now(),
        );

        return $audit;
    }

    private function priceList(): PriceList
    {
        return PriceList::createDraft(
            PriceListId::fromString(self::PRICE_LIST_ID, new SymfonyUuidFactory()),
            $this->organizationId(),
            PriceListCode::fromString('BASE'),
            PriceListName::fromString('Base'),
            Currency::fromCode('XOF'),
            null,
            null,
            PriceListPriority::fromInt(0),
            $this->context()->actorId(),
            new DateTimeImmutable('2026-08-25T08:00:00Z'),
        );
    }

    private function productPrice(PriceList $priceList): ProductPrice
    {
        $factory = new SymfonyUuidFactory();

        return ProductPrice::createActive(
            ProductPriceId::fromString(self::PRODUCT_PRICE_ID, $factory),
            $priceList,
            new ProductPriceTarget(
                $this->organizationId(),
                ProductId::fromString(self::PRODUCT_ID, $factory),
                ProductPackagingId::fromString(self::PACKAGING_ID, $factory),
            ),
            Money::fromString('1000', Currency::fromCode('XOF'), new BrickDecimalFactory()),
            null,
            null,
            $this->context()->actorId(),
            new DateTimeImmutable('2026-08-25T08:00:00Z'),
        );
    }

    private function context(): ActorContext
    {
        $factory = new SymfonyUuidFactory();

        return new ActorContext(
            ActorId::fromString(self::ACTOR_ID, $factory),
            $this->organizationId(),
            ActorType::User,
            CorrelationId::fromString(self::CORRELATION_ID, $factory),
            new DateTimeImmutable('2026-08-25T09:00:00Z'),
        );
    }

    private function organizationId(): OrganizationId
    {
        return OrganizationId::fromString(self::ORGANIZATION_ID, new SymfonyUuidFactory());
    }

    private function clock(): FrozenClock
    {
        return new FrozenClock(new DateTimeImmutable('2026-08-25T10:00:00Z'));
    }

    private function transaction(): TenantTransaction
    {
        return new class implements TenantTransaction {
            public function transactional(OrganizationId $organizationId, callable $operation): mixed
            {
                TestCase::assertSame('0198d1b1-b2a4-7b6e-8e0e-608484906502', $organizationId->toString());

                return $operation();
            }
        };
    }
}
