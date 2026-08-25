<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Application\ArchiveProduct;

use Zandu\Modules\Catalog\Application\TenantProductLoader;
use Zandu\Modules\Catalog\Domain\Product\Product;
use Zandu\Modules\Catalog\Domain\Product\ProductRepository;
use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Access\ResourceScope;
use Zandu\SharedKernel\SecurityAudit\ResourceReference;
use Zandu\SharedKernel\SecurityAudit\SafeAuditMetadata;
use Zandu\SharedKernel\SecurityAudit\SecurityAction;
use Zandu\SharedKernel\SecurityAudit\SecurityAuditTrail;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class ArchiveProductHandler
{
    public function __construct(
        private TenantProductLoader $loader,
        private ProductRepository $products,
        private Clock $clock,
        private TenantTransaction $transaction,
        private AuthorizationService $authorization,
        private OperationalGuard $operationalGuard,
        private SecurityAuditTrail $audit,
    ) {}

    public function __invoke(ArchiveProduct $command): Product
    {
        $organizationId = $command->actorContext->organizationId();

        return $this->transaction->transactional($organizationId, function () use ($command, $organizationId): Product {
            $this->authorization->authorize($command->actorContext, PermissionCode::ProductArchive, ResourceScope::organization($organizationId));
            $this->operationalGuard->assertTenant($command->actorContext);
            $product = $this->loader->get($command->productId, $command->actorContext);
            $now = $this->clock->now();
            $product->archive($command->actorContext->actorId(), $now);
            $this->products->save($product);
            $this->audit->recordSuccess($command->actorContext, SecurityAction::ProductArchived, ResourceReference::for('product', $product->id()), SafeAuditMetadata::empty(), $now);

            return $product;
        });
    }
}
