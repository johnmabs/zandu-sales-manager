<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Catalog\Application;

use DateTimeImmutable;
use LogicException;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\Catalog\Application\ActivateProduct\ActivateProduct;
use Zandu\Modules\Catalog\Application\ActivateProduct\ActivateProductHandler;
use Zandu\Modules\Catalog\Application\ArchiveProduct\ArchiveProduct;
use Zandu\Modules\Catalog\Application\ArchiveProduct\ArchiveProductHandler;
use Zandu\Modules\Catalog\Application\Contract\BasePackagingPresence;
use Zandu\Modules\Catalog\Application\CreateProduct\CreateProduct;
use Zandu\Modules\Catalog\Application\CreateProduct\CreateProductHandler;
use Zandu\Modules\Catalog\Application\DeactivateProduct\DeactivateProduct;
use Zandu\Modules\Catalog\Application\DeactivateProduct\DeactivateProductHandler;
use Zandu\Modules\Catalog\Application\ReactivateProduct\ReactivateProduct;
use Zandu\Modules\Catalog\Application\ReactivateProduct\ReactivateProductHandler;
use Zandu\Modules\Catalog\Application\TenantCategoryLoader;
use Zandu\Modules\Catalog\Application\TenantProductLoader;
use Zandu\Modules\Catalog\Application\TenantUnitOfMeasureLoader;
use Zandu\Modules\Catalog\Application\UpdateProduct\UpdateProduct;
use Zandu\Modules\Catalog\Application\UpdateProduct\UpdateProductHandler;
use Zandu\Modules\Catalog\Domain\Category\Category;
use Zandu\Modules\Catalog\Domain\Category\CategoryName;
use Zandu\Modules\Catalog\Domain\Category\CategoryRepository;
use Zandu\Modules\Catalog\Domain\Product\Product;
use Zandu\Modules\Catalog\Domain\Product\ProductCode;
use Zandu\Modules\Catalog\Domain\Product\ProductCodeAlreadyExists;
use Zandu\Modules\Catalog\Domain\Product\ProductName;
use Zandu\Modules\Catalog\Domain\Product\ProductRepository;
use Zandu\Modules\Catalog\Domain\Product\ProductStatus;
use Zandu\Modules\Catalog\Domain\Product\ProductType;
use Zandu\Modules\Catalog\Domain\UnitOfMeasure;
use Zandu\Modules\Catalog\Domain\UnitOfMeasureCode;
use Zandu\Modules\Catalog\Domain\UnitOfMeasureDimension;
use Zandu\Modules\Catalog\Domain\UnitOfMeasureName;
use Zandu\Modules\Catalog\Domain\UnitOfMeasurePrecision;
use Zandu\Modules\Catalog\Domain\UnitOfMeasureRepository;
use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Access\ResourceScope;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Context\ActorType;
use Zandu\SharedKernel\Decimal\RoundingMode;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\CategoryId;
use Zandu\SharedKernel\Identity\IdGenerator;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\TaxCategoryId;
use Zandu\SharedKernel\Identity\UnitOfMeasureId;
use Zandu\SharedKernel\Identity\Uuid;
use Zandu\SharedKernel\Messaging\CorrelationId;
use Zandu\SharedKernel\SecurityAudit\ResourceReference;
use Zandu\SharedKernel\SecurityAudit\SafeAuditMetadata;
use Zandu\SharedKernel\SecurityAudit\SecurityAction;
use Zandu\SharedKernel\SecurityAudit\SecurityAuditTrail;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\Tests\SharedKernel\Time\FrozenClock;

final class CreateProductHandlerTest extends TestCase
{
    private const ORGANIZATION_ID = '0198d2b1-b2a4-7b6e-8e0e-608484906502';
    private const PRODUCT_ID = '0198d2b2-147c-72d5-b75a-a936797ff9c8';
    private const UNIT_ID = '0198d2b3-147c-72d5-b75a-a936797ff9c8';
    private const CATEGORY_ID = '0198d2b4-147c-72d5-b75a-a936797ff9c8';
    private const TAX_CATEGORY_ID = '0198d2b5-147c-72d5-b75a-a936797ff9c8';
    private const ACTOR_ID = '0198c728-8f2d-7f43-92d8-3f0c75b80186';
    private const CORRELATION_ID = '0198c729-19da-75be-b508-1a4b36cf8d7a';

    public function testItCreatesADraftUsingTenantScopedSelectableReferences(): void
    {
        $context = $this->context();
        $unit = $this->unit();
        $category = $this->category();
        $products = $this->createMock(ProductRepository::class);
        $products->expects(self::once())->method('findByCode')->with(
            self::callback(fn(OrganizationId $id): bool => $id->equals($context->organizationId())),
            self::callback(fn(ProductCode $code): bool => 'SKU-001' === $code->value()),
        )->willReturn(null);
        $products->expects(self::once())->method('save')->with(self::isInstanceOf(Product::class));
        $units = $this->createMock(UnitOfMeasureRepository::class);
        $units->expects(self::once())->method('get')->with(
            self::callback(fn(OrganizationId $id): bool => $id->equals($context->organizationId())),
            self::callback(fn(UnitOfMeasureId $id): bool => $id->equals($unit->id())),
        )->willReturn($unit);
        $categories = $this->createMock(CategoryRepository::class);
        $categories->expects(self::once())->method('get')->with(
            self::callback(fn(OrganizationId $id): bool => $id->equals($context->organizationId())),
            self::callback(fn(CategoryId $id): bool => $id->equals($category->id())),
        )->willReturn($category);
        $authorization = $this->createMock(AuthorizationService::class);
        $authorization->expects(self::once())->method('authorize')->with(
            $context,
            PermissionCode::ProductCreate,
            self::callback(fn(ResourceScope $scope): bool => $scope->organizationId->equals($context->organizationId()) && null === $scope->storeId),
        );
        $guard = $this->createMock(OperationalGuard::class);
        $guard->expects(self::once())->method('assertTenant')->with($context);

        $product = $this->handler($products, $units, $categories, $authorization, $guard)(
            $this->command($context),
        );

        self::assertSame(self::PRODUCT_ID, $product->id()->toString());
        self::assertSame(ProductStatus::Draft, $product->status());
        self::assertTrue($product->baseUnitId()->equals($unit->id()));
        self::assertTrue($product->categoryId()?->equals($category->id()));
        self::assertSame(self::TAX_CATEGORY_ID, $product->taxCategoryId()?->toString());
    }

    public function testItRejectsADuplicateCodeBeforeLoadingReferences(): void
    {
        $products = $this->createStub(ProductRepository::class);
        $products->method('findByCode')->willReturn($this->existingProduct());
        $units = $this->createMock(UnitOfMeasureRepository::class);
        $units->expects(self::never())->method('get');

        $this->expectException(ProductCodeAlreadyExists::class);
        $this->handler(
            $products,
            $units,
            $this->createStub(CategoryRepository::class),
            $this->createStub(AuthorizationService::class),
            $this->createStub(OperationalGuard::class),
        )($this->command($this->context()));
    }

    public function testItRejectsAnInactiveBaseUnit(): void
    {
        $unit = $this->unit();
        $unit->deactivate($this->actorId(), new DateTimeImmutable('2026-08-25T12:00:00Z'));
        $products = $this->createStub(ProductRepository::class);
        $products->method('findByCode')->willReturn(null);
        $units = $this->createStub(UnitOfMeasureRepository::class);
        $units->method('get')->willReturn($unit);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('inactive unit of measure');
        $this->handler(
            $products,
            $units,
            $this->createStub(CategoryRepository::class),
            $this->createStub(AuthorizationService::class),
            $this->createStub(OperationalGuard::class),
        )($this->command($this->context()));
    }

    public function testItRejectsAnInactiveCategory(): void
    {
        $category = $this->category();
        $category->deactivate($this->actorId(), new DateTimeImmutable('2026-08-25T12:00:00Z'));
        $products = $this->createStub(ProductRepository::class);
        $products->method('findByCode')->willReturn(null);
        $units = $this->createStub(UnitOfMeasureRepository::class);
        $units->method('get')->willReturn($this->unit());
        $categories = $this->createStub(CategoryRepository::class);
        $categories->method('get')->willReturn($category);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('active category');
        $this->handler(
            $products,
            $units,
            $categories,
            $this->createStub(AuthorizationService::class),
            $this->createStub(OperationalGuard::class),
        )($this->command($this->context()));
    }

    public function testServiceCannotEnableInventoryTracking(): void
    {
        $products = $this->createStub(ProductRepository::class);
        $products->method('findByCode')->willReturn(null);
        $units = $this->createStub(UnitOfMeasureRepository::class);
        $units->method('get')->willReturn($this->unit());
        $categories = $this->createStub(CategoryRepository::class);
        $categories->method('get')->willReturn($this->category());
        $command = $this->command($this->context(), 'SERVICE');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('service product cannot be inventory tracked');
        $this->handler(
            $products,
            $units,
            $categories,
            $this->createStub(AuthorizationService::class),
            $this->createStub(OperationalGuard::class),
        )($command);
    }

    public function testItUpdatesOnlyTheDraftProductProfile(): void
    {
        $context = $this->context();
        $product = $this->existingProduct();
        $products = $this->createMock(ProductRepository::class);
        $products->expects(self::once())->method('get')->willReturn($product);
        $products->expects(self::once())->method('findByCode')->willReturn($product);
        $products->expects(self::once())->method('save')->with($product);
        $units = $this->createStub(UnitOfMeasureRepository::class);
        $units->method('get')->willReturn($this->unit());
        $categories = $this->createStub(CategoryRepository::class);
        $categories->method('get')->willReturn($this->category());
        $authorization = $this->createMock(AuthorizationService::class);
        $authorization->expects(self::once())->method('authorize')->with(
            $context,
            PermissionCode::ProductUpdate,
            self::isInstanceOf(ResourceScope::class),
        );
        $guard = $this->createMock(OperationalGuard::class);
        $guard->expects(self::once())->method('assertTenant')->with($context);

        $updated = $this->updateHandler($products, $units, $categories, $authorization, $guard)(
            $this->updateCommand($context),
        );

        self::assertSame('Café premium', $updated->name()->value());
        self::assertSame('Nouvelle description', $updated->description());
        self::assertSame(ProductStatus::Draft, $updated->status());
        self::assertSame(2, $updated->version());
    }

    public function testUpdateRejectsACodeOwnedByAnotherProduct(): void
    {
        $product = $this->existingProduct();
        $otherProduct = Product::createDraft(
            ProductId::fromString('0198d2b6-147c-72d5-b75a-a936797ff9c8', new SymfonyUuidFactory()),
            $this->organizationId(),
            ProductCode::fromString('SKU-002'),
            ProductName::fromString('Other'),
            null,
            ProductType::Physical,
            $this->unit()->id(),
            true,
            null,
            null,
            $this->actorId(),
            new DateTimeImmutable('2026-08-25T10:00:00Z'),
        );
        $products = $this->createStub(ProductRepository::class);
        $products->method('get')->willReturn($product);
        $products->method('findByCode')->willReturn($otherProduct);

        $this->expectException(ProductCodeAlreadyExists::class);
        $this->updateHandler(
            $products,
            $this->createStub(UnitOfMeasureRepository::class),
            $this->createStub(CategoryRepository::class),
            $this->createStub(AuthorizationService::class),
            $this->createStub(OperationalGuard::class),
        )($this->updateCommand($this->context()));
    }

    public function testUpdateCannotChangeCodeAfterFirstActivation(): void
    {
        $product = $this->existingProduct();
        $product->activate(true, $this->actorId(), new DateTimeImmutable('2026-08-25T10:30:00Z'));
        $products = $this->createStub(ProductRepository::class);
        $products->method('get')->willReturn($product);
        $products->method('findByCode')->willReturn(null);
        $units = $this->createStub(UnitOfMeasureRepository::class);
        $units->method('get')->willReturn($this->unit());
        $categories = $this->createStub(CategoryRepository::class);
        $categories->method('get')->willReturn($this->category());
        $command = $this->updateCommand($this->context(), 'SKU-002');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('code is immutable after first activation');
        $this->updateHandler(
            $products,
            $units,
            $categories,
            $this->createStub(AuthorizationService::class),
            $this->createStub(OperationalGuard::class),
        )($command);
    }

    public function testItActivatesAConsistentProductWithABasePackaging(): void
    {
        $context = $this->context();
        $product = $this->existingProduct();
        $products = $this->createMock(ProductRepository::class);
        $products->expects(self::once())->method('get')->willReturn($product);
        $products->expects(self::once())->method('save')->with($product);
        $units = $this->createMock(UnitOfMeasureRepository::class);
        $units->expects(self::once())->method('get')->willReturn($this->unit());
        $presence = $this->createMock(BasePackagingPresence::class);
        $presence->expects(self::once())->method('exists')->with(
            self::callback(fn(OrganizationId $id): bool => $id->equals($context->organizationId())),
            self::callback(fn(ProductId $id): bool => $id->equals($product->id())),
            self::callback(fn(UnitOfMeasureId $id): bool => $id->equals($product->baseUnitId())),
        )->willReturn(true);
        $authorization = $this->createMock(AuthorizationService::class);
        $authorization->expects(self::once())->method('authorize')->with(
            $context,
            PermissionCode::ProductActivate,
            self::isInstanceOf(ResourceScope::class),
        );
        $audit = $this->auditExpecting(SecurityAction::ProductActivated, 'PRODUCT', self::PRODUCT_ID);

        $activated = $this->activateHandler(
            $products,
            $units,
            $presence,
            $authorization,
            $this->createStub(OperationalGuard::class),
            $audit,
        )(new ActivateProduct($product->id(), $context));

        self::assertSame(ProductStatus::Active, $activated->status());
        self::assertSame(2, $activated->version());
        self::assertNotNull($activated->activatedAt());
    }

    public function testActivationFailsClosedWithoutPersistedBasePackaging(): void
    {
        $product = $this->existingProduct();
        $products = $this->createStub(ProductRepository::class);
        $products->method('get')->willReturn($product);
        $units = $this->createStub(UnitOfMeasureRepository::class);
        $units->method('get')->willReturn($this->unit());
        $presence = $this->createStub(BasePackagingPresence::class);
        $presence->method('exists')->willReturn(false);

        self::assertFalse($presence->exists($this->organizationId(), $product->id(), $product->baseUnitId()));
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('base packaging is required');
        $this->activateHandler(
            $products,
            $units,
            $presence,
            $this->createStub(AuthorizationService::class),
            $this->createStub(OperationalGuard::class),
        )(new ActivateProduct($product->id(), $this->context()));
    }

    public function testAvailabilityLifecycleUsesExplicitCommandsAndPermissions(): void
    {
        $context = $this->context();
        $product = $this->existingProduct();
        $product->activate(true, $this->actorId(), new DateTimeImmutable('2026-08-25T10:30:00Z'));
        $products = $this->createStub(ProductRepository::class);
        $products->method('get')->willReturn($product);
        $guard = $this->createStub(OperationalGuard::class);

        $deactivateAuthorization = $this->authorizationExpecting(PermissionCode::ProductDeactivate);
        $deactivated = $this->deactivateHandler($products, $deactivateAuthorization, $guard)(
            new DeactivateProduct($product->id(), $context),
        );
        self::assertSame(ProductStatus::Inactive, $deactivated->status());

        $reactivateAuthorization = $this->authorizationExpecting(PermissionCode::ProductActivate);
        $reactivated = $this->reactivateHandler($products, $reactivateAuthorization, $guard)(
            new ReactivateProduct($product->id(), $context),
        );
        self::assertSame(ProductStatus::Active, $reactivated->status());

        $archiveAuthorization = $this->authorizationExpecting(PermissionCode::ProductArchive);
        $archived = $this->archiveHandler(
            $products,
            $archiveAuthorization,
            $guard,
            $this->auditExpecting(SecurityAction::ProductArchived, 'PRODUCT', self::PRODUCT_ID),
        )(
            new ArchiveProduct($product->id(), $context),
        );
        self::assertSame(ProductStatus::Archived, $archived->status());
        self::assertSame(5, $archived->version());

        $this->expectException(LogicException::class);
        $this->reactivateHandler(
            $products,
            $this->createStub(AuthorizationService::class),
            $guard,
        )(new ReactivateProduct($product->id(), $context));
    }

    private function handler(
        ProductRepository $products,
        UnitOfMeasureRepository $units,
        CategoryRepository $categories,
        AuthorizationService $authorization,
        OperationalGuard $guard,
    ): CreateProductHandler {
        $uuid = (new SymfonyUuidFactory())->fromString(self::PRODUCT_ID);

        return new CreateProductHandler(
            $products,
            new TenantUnitOfMeasureLoader($units),
            new TenantCategoryLoader($categories),
            new class ($uuid) implements IdGenerator {
                public function __construct(private readonly Uuid $uuid) {}
                public function generate(): Uuid
                {
                    return $this->uuid;
                }
            },
            new FrozenClock(new DateTimeImmutable('2026-08-25T11:00:00Z')),
            new class implements TenantTransaction {
                public function transactional(OrganizationId $organizationId, callable $operation): mixed
                {
                    return $operation();
                }
            },
            $authorization,
            $guard,
        );
    }

    private function updateHandler(
        ProductRepository $products,
        UnitOfMeasureRepository $units,
        CategoryRepository $categories,
        AuthorizationService $authorization,
        OperationalGuard $guard,
    ): UpdateProductHandler {
        return new UpdateProductHandler(
            new TenantProductLoader($products),
            $products,
            new TenantUnitOfMeasureLoader($units),
            new TenantCategoryLoader($categories),
            new FrozenClock(new DateTimeImmutable('2026-08-25T13:00:00Z')),
            new class implements TenantTransaction {
                public function transactional(OrganizationId $organizationId, callable $operation): mixed
                {
                    return $operation();
                }
            },
            $authorization,
            $guard,
        );
    }

    private function activateHandler(
        ProductRepository $products,
        UnitOfMeasureRepository $units,
        BasePackagingPresence $presence,
        AuthorizationService $authorization,
        OperationalGuard $guard,
        ?SecurityAuditTrail $audit = null,
    ): ActivateProductHandler {
        return new ActivateProductHandler(
            new TenantProductLoader($products),
            $products,
            new TenantUnitOfMeasureLoader($units),
            $presence,
            new FrozenClock(new DateTimeImmutable('2026-08-25T14:00:00Z')),
            new class implements TenantTransaction {
                public function transactional(OrganizationId $organizationId, callable $operation): mixed
                {
                    return $operation();
                }
            },
            $authorization,
            $guard,
            $audit ?? $this->createStub(SecurityAuditTrail::class),
        );
    }

    private function deactivateHandler(
        ProductRepository $products,
        AuthorizationService $authorization,
        OperationalGuard $guard,
    ): DeactivateProductHandler {
        return new DeactivateProductHandler(
            new TenantProductLoader($products),
            $products,
            new FrozenClock(new DateTimeImmutable('2026-08-25T15:00:00Z')),
            $this->transaction(),
            $authorization,
            $guard,
        );
    }

    private function reactivateHandler(
        ProductRepository $products,
        AuthorizationService $authorization,
        OperationalGuard $guard,
    ): ReactivateProductHandler {
        return new ReactivateProductHandler(
            new TenantProductLoader($products),
            $products,
            new FrozenClock(new DateTimeImmutable('2026-08-25T16:00:00Z')),
            $this->transaction(),
            $authorization,
            $guard,
        );
    }

    private function archiveHandler(
        ProductRepository $products,
        AuthorizationService $authorization,
        OperationalGuard $guard,
        ?SecurityAuditTrail $audit = null,
    ): ArchiveProductHandler {
        return new ArchiveProductHandler(
            new TenantProductLoader($products),
            $products,
            new FrozenClock(new DateTimeImmutable('2026-08-25T17:00:00Z')),
            $this->transaction(),
            $authorization,
            $guard,
            $audit ?? $this->createStub(SecurityAuditTrail::class),
        );
    }

    private function auditExpecting(SecurityAction $action, string $type, string $id): SecurityAuditTrail
    {
        $audit = $this->createMock(SecurityAuditTrail::class);
        $audit->expects(self::once())->method('recordSuccess')->with(
            self::isInstanceOf(ActorContext::class),
            $action,
            self::callback(static fn(ResourceReference $target): bool => $type === $target->type && $id === $target->id),
            self::isInstanceOf(SafeAuditMetadata::class),
            self::isInstanceOf(DateTimeImmutable::class),
        );

        return $audit;
    }

    private function authorizationExpecting(PermissionCode $permission): AuthorizationService
    {
        $authorization = $this->createMock(AuthorizationService::class);
        $authorization->expects(self::once())->method('authorize')->with(
            self::isInstanceOf(ActorContext::class),
            $permission,
            self::isInstanceOf(ResourceScope::class),
        );

        return $authorization;
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

    private function command(ActorContext $context, string $type = 'PHYSICAL'): CreateProduct
    {
        $factory = new SymfonyUuidFactory();

        return new CreateProduct(
            ' sku-001 ',
            ' Café moulu ',
            ' Paquet de café ',
            $type,
            UnitOfMeasureId::fromString(self::UNIT_ID, $factory),
            true,
            CategoryId::fromString(self::CATEGORY_ID, $factory),
            TaxCategoryId::fromString(self::TAX_CATEGORY_ID, $factory),
            $context,
        );
    }

    private function updateCommand(ActorContext $context, string $code = 'SKU-001'): UpdateProduct
    {
        $factory = new SymfonyUuidFactory();

        return new UpdateProduct(
            ProductId::fromString(self::PRODUCT_ID, $factory),
            $code,
            ' Café premium ',
            ' Nouvelle description ',
            'PHYSICAL',
            UnitOfMeasureId::fromString(self::UNIT_ID, $factory),
            true,
            CategoryId::fromString(self::CATEGORY_ID, $factory),
            TaxCategoryId::fromString(self::TAX_CATEGORY_ID, $factory),
            $context,
        );
    }

    private function existingProduct(): Product
    {
        return Product::createDraft(
            ProductId::fromString(self::PRODUCT_ID, new SymfonyUuidFactory()),
            $this->organizationId(),
            ProductCode::fromString('SKU-001'),
            ProductName::fromString('Existing'),
            null,
            ProductType::Physical,
            $this->unit()->id(),
            true,
            null,
            null,
            $this->actorId(),
            new DateTimeImmutable('2026-08-25T10:00:00Z'),
        );
    }

    private function unit(): UnitOfMeasure
    {
        return UnitOfMeasure::create(
            UnitOfMeasureId::fromString(self::UNIT_ID, new SymfonyUuidFactory()),
            $this->organizationId(),
            UnitOfMeasureCode::fromString('EA'),
            UnitOfMeasureName::fromString('Article'),
            UnitOfMeasureDimension::Count,
            UnitOfMeasurePrecision::fromInt(0),
            RoundingMode::HalfUp,
            $this->actorId(),
            new DateTimeImmutable('2026-08-25T10:00:00Z'),
        );
    }

    private function category(): Category
    {
        return Category::create(
            CategoryId::fromString(self::CATEGORY_ID, new SymfonyUuidFactory()),
            $this->organizationId(),
            CategoryName::fromString('Café'),
            null,
            $this->actorId(),
            new DateTimeImmutable('2026-08-25T10:00:00Z'),
        );
    }

    private function context(): ActorContext
    {
        $factory = new SymfonyUuidFactory();

        return new ActorContext(
            $this->actorId(),
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

    private function actorId(): ActorId
    {
        return ActorId::fromString(self::ACTOR_ID, new SymfonyUuidFactory());
    }
}
