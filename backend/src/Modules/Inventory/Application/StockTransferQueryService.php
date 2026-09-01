<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application;

use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Inventory\Domain\StockTransfer\{StockTransfer, StockTransferRepository};
use Zandu\SharedKernel\Access\{AuthorizationDenied, PermissionCode, ResourceScope};
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\StockTransferId;

final readonly class StockTransferQueryService
{
    public function __construct(private StockTransferRepository $transfers, private AuthorizationService $authorization, private StockTransferViewFactory $views) {}

    public function get(ActorContext $actor, StockTransferId $transferId): StockTransferView
    {
        $transfer = $this->transfers->get($actor->organizationId(), $transferId);
        $this->authorizeRead($actor, $transfer);

        return $this->views->create($transfer);
    }

    /** @return list<StockTransferView> */
    public function list(ActorContext $actor): array
    {
        $views = [];
        foreach ($this->transfers->findAll($actor->organizationId()) as $transfer) {
            try {
                $this->authorizeRead($actor, $transfer);
            } catch (AuthorizationDenied) {
                continue;
            }
            $views[] = $this->views->create($transfer);
        }

        return $views;
    }

    private function authorizeRead(ActorContext $actor, StockTransfer $transfer): void
    {
        try {
            $this->authorization->authorize($actor, PermissionCode::StockTransferRead, ResourceScope::store($actor->organizationId(), $transfer->sourceStoreId()));
        } catch (AuthorizationDenied) {
            $this->authorization->authorize($actor, PermissionCode::StockTransferRead, ResourceScope::store($actor->organizationId(), $transfer->destinationStoreId()));
        }
    }
}
