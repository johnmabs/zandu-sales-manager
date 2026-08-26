<?php

declare(strict_types=1);

namespace Zandu\Modules\Pricing\Application;

use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\Modules\Pricing\Domain\ProductPrice\ProductPrice;
use Zandu\Modules\Pricing\Domain\ProductPrice\ProductPriceRepository;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Access\ResourceScope;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class ActivateProductPriceHandler
{
    public function __construct(
        private ProductPriceRepository $prices,
        private Clock $clock,
        private TenantTransaction $transaction,
        private AuthorizationService $authorization,
        private OperationalGuard $guard,
    ) {}

    public function __invoke(ActivateProductPrice $command): ProductPrice
    {
        $organizationId = $command->actor->organizationId();

        return $this->transaction->transactional($organizationId, function () use ($command, $organizationId): ProductPrice {
            $this->authorization->authorize($command->actor, PermissionCode::ProductPriceUpdate, ResourceScope::organization($organizationId));
            $this->guard->assertTenant($command->actor);
            $price = $this->prices->get($organizationId, $command->id);
            $price->activate($command->actor->actorId(), $this->clock->now());
            $this->prices->save($price);

            return $price;
        });
    }
}
