<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Application;

use LogicException;
use Zandu\Modules\Catalog\Domain\ProductBarcode\Barcode;
use Zandu\Modules\Catalog\Domain\ProductBarcode\ProductBarcode;
use Zandu\Modules\Catalog\Domain\ProductBarcode\ProductBarcodeRepository;
use Zandu\Modules\Catalog\Domain\ProductPackaging\ProductPackagingRepository;
use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Access\ResourceScope;
use Zandu\SharedKernel\Identity\IdGenerator;
use Zandu\SharedKernel\Identity\ProductBarcodeId;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class AddProductBarcodeHandler
{
    public function __construct(
        private ProductBarcodeRepository $barcodes,
        private ProductPackagingRepository $packagings,
        private IdGenerator $ids,
        private Clock $clock,
        private TenantTransaction $transaction,
        private AuthorizationService $authorization,
        private OperationalGuard $guard,
    ) {}

    public function __invoke(AddProductBarcode $command): ProductBarcode
    {
        $organizationId = $command->actorContext->organizationId();

        return $this->transaction->transactional($organizationId, function () use ($command, $organizationId): ProductBarcode {
            $this->authorization->authorize($command->actorContext, PermissionCode::ProductUpdate, ResourceScope::organization($organizationId));
            $this->guard->assertTenant($command->actorContext);
            $packaging = $this->packagings->get($organizationId, $command->packagingId);
            $barcode = Barcode::fromString($command->barcode);
            if (null !== $this->barcodes->findByBarcode($organizationId, $barcode)) {
                throw new LogicException('Barcode already exists for this organization.');
            }

            $aggregate = ProductBarcode::add(ProductBarcodeId::generate($this->ids), $packaging, $barcode, $command->actorContext->actorId(), $this->clock->now());
            $this->barcodes->save($aggregate);

            return $aggregate;
        });
    }
}
