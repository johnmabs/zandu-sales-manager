<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Application;

/** Marker for Symfony's PSR-4 service discovery of this grouped handler file. */
final class ProductPackagingCommandHandlers {}

use Zandu\Modules\Catalog\Domain\ProductPackaging\ConversionFactor;
use Zandu\Modules\Catalog\Domain\ProductPackaging\ProductPackaging;
use Zandu\Modules\Catalog\Domain\ProductPackaging\ProductPackagingCode;
use Zandu\Modules\Catalog\Domain\ProductPackaging\ProductPackagingName;
use Zandu\Modules\Catalog\Domain\ProductPackaging\ProductPackagingPrecision;
use Zandu\Modules\Catalog\Domain\ProductPackaging\ProductPackagingRepository;
use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Access\ResourceScope;
use Zandu\SharedKernel\Decimal\DecimalFactory;
use Zandu\SharedKernel\Identity\IdGenerator;
use Zandu\SharedKernel\Identity\ProductPackagingId;
use Zandu\SharedKernel\Quantity\Quantity;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class CreateProductPackagingHandler
{
    public function __construct(private ProductPackagingRepository $packagings, private IdGenerator $ids, private DecimalFactory $decimals, private Clock $clock, private TenantTransaction $transaction, private AuthorizationService $authorization, private OperationalGuard $guard) {}
    public function __invoke(CreateProductPackaging $command): ProductPackaging
    {
        $org = $command->actorContext->organizationId();
        return $this->transaction->transactional($org, function () use ($command, $org): ProductPackaging {
            $this->authorization->authorize($command->actorContext, PermissionCode::ProductUpdate, ResourceScope::organization($org));
            $this->guard->assertTenant($command->actorContext);
            $code = ProductPackagingCode::fromString($command->code);
            if (null !== $this->packagings->findByCode($org, $command->productId, $code)) {
                throw new \LogicException('Product packaging code already exists.');
            }
            $packaging = ProductPackaging::createAdditional(ProductPackagingId::generate($this->ids), $org, $command->productId, $code, ProductPackagingName::fromString($command->name), $command->unitId, new ConversionFactor($this->decimals->fromString($command->conversionFactor)), ProductPackagingPrecision::fromInt($command->precision), Quantity::fromString($command->minimumQuantity, $this->decimals), Quantity::fromString($command->quantityIncrement, $this->decimals), $command->allowedForSale, $command->allowedForPurchase, $command->actorContext->actorId(), $this->clock->now());
            $this->packagings->save($packaging);
            return $packaging;
        });
    }
}
final readonly class UpdateProductPackagingHandler
{
    public function __construct(private ProductPackagingRepository $packagings, private DecimalFactory $decimals, private Clock $clock, private TenantTransaction $transaction, private AuthorizationService $authorization, private OperationalGuard $guard) {}
    public function __invoke(UpdateProductPackaging $command): ProductPackaging
    {
        $org = $command->actorContext->organizationId();
        return $this->transaction->transactional($org, function () use ($command, $org): ProductPackaging {
            $this->authorization->authorize($command->actorContext, PermissionCode::ProductUpdate, ResourceScope::organization($org));
            $this->guard->assertTenant($command->actorContext);
            $p = $this->packagings->get($org, $command->packagingId);
            $p->updateCommercialSettings(ProductPackagingName::fromString($command->name), Quantity::fromString($command->minimumQuantity, $this->decimals), Quantity::fromString($command->quantityIncrement, $this->decimals), $command->allowedForSale, $command->allowedForPurchase, $command->actorContext->actorId(), $this->clock->now());
            $this->packagings->save($p);
            return $p;
        });
    }
}
final readonly class DeactivateProductPackagingHandler
{
    public function __construct(private ProductPackagingRepository $packagings, private Clock $clock, private TenantTransaction $transaction, private AuthorizationService $authorization, private OperationalGuard $guard) {}
    public function __invoke(DeactivateProductPackaging $command): ProductPackaging
    {
        $org = $command->actorContext->organizationId();
        return $this->transaction->transactional($org, function () use ($command, $org): ProductPackaging {
            $this->authorization->authorize($command->actorContext, PermissionCode::ProductUpdate, ResourceScope::organization($org));
            $this->guard->assertTenant($command->actorContext);
            $p = $this->packagings->get($org, $command->packagingId);
            $p->deactivate($command->actorContext->actorId(), $this->clock->now());
            $this->packagings->save($p);
            return $p;
        });
    }
}
final readonly class ArchiveProductPackagingHandler
{
    public function __construct(private ProductPackagingRepository $packagings, private Clock $clock, private TenantTransaction $transaction, private AuthorizationService $authorization, private OperationalGuard $guard) {}
    public function __invoke(ArchiveProductPackaging $command): ProductPackaging
    {
        $org = $command->actorContext->organizationId();
        return $this->transaction->transactional($org, function () use ($command, $org): ProductPackaging {
            $this->authorization->authorize($command->actorContext, PermissionCode::ProductArchive, ResourceScope::organization($org));
            $this->guard->assertTenant($command->actorContext);
            $p = $this->packagings->get($org, $command->packagingId);
            $p->archive($command->actorContext->actorId(), $this->clock->now());
            $this->packagings->save($p);
            return $p;
        });
    }
}
