<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Application;

use Zandu\Modules\Catalog\Domain\ProductPackaging\ProductPackaging;
use Zandu\Modules\Catalog\Domain\ProductPackaging\ProductPackagingName;
use Zandu\Modules\Catalog\Domain\ProductPackaging\ProductPackagingRepository;
use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Access\ResourceScope;
use Zandu\SharedKernel\Decimal\DecimalFactory;
use Zandu\SharedKernel\Quantity\Quantity;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class UpdateProductPackagingHandler
{
    public function __construct(
        private ProductPackagingRepository $packagings,
        private DecimalFactory $decimals,
        private Clock $clock,
        private TenantTransaction $transaction,
        private AuthorizationService $authorization,
        private OperationalGuard $guard,
    ) {}

    public function __invoke(UpdateProductPackaging $command): ProductPackaging
    {
        $organizationId = $command->actorContext->organizationId();

        return $this->transaction->transactional($organizationId, function () use ($command, $organizationId): ProductPackaging {
            $this->authorization->authorize($command->actorContext, PermissionCode::ProductUpdate, ResourceScope::organization($organizationId));
            $this->guard->assertTenant($command->actorContext);
            $packaging = $this->packagings->get($organizationId, $command->packagingId);
            $packaging->updateCommercialSettings(ProductPackagingName::fromString($command->name), Quantity::fromString($command->minimumQuantity, $this->decimals), Quantity::fromString($command->quantityIncrement, $this->decimals), $command->allowedForSale, $command->allowedForPurchase, $command->actorContext->actorId(), $this->clock->now());
            $this->packagings->save($packaging);

            return $packaging;
        });
    }
}
