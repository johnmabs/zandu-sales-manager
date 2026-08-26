<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Application;

use Zandu\Modules\Catalog\Domain\ProductBarcode\ProductBarcode;
use Zandu\Modules\Catalog\Domain\ProductBarcode\ProductBarcodeRepository;
use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Access\ResourceScope;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class RemoveProductBarcodeHandler
{
    public function __construct(
        private ProductBarcodeRepository $barcodes,
        private Clock $clock,
        private TenantTransaction $transaction,
        private AuthorizationService $authorization,
        private OperationalGuard $guard,
    ) {}

    public function __invoke(RemoveProductBarcode $command): ProductBarcode
    {
        $organizationId = $command->actorContext->organizationId();

        return $this->transaction->transactional($organizationId, function () use ($command, $organizationId): ProductBarcode {
            $this->authorization->authorize($command->actorContext, PermissionCode::ProductUpdate, ResourceScope::organization($organizationId));
            $this->guard->assertTenant($command->actorContext);
            $barcode = $this->barcodes->get($organizationId, $command->barcodeId);
            $barcode->remove($command->actorContext->actorId(), $this->clock->now());
            $this->barcodes->save($barcode);

            return $barcode;
        });
    }
}
