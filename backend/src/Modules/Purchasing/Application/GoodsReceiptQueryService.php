<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Application;

use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Purchasing\Domain\GoodsReceipt\GoodsReceipt;
use Zandu\Modules\Purchasing\Domain\GoodsReceipt\GoodsReceiptRepository;
use Zandu\SharedKernel\Access\AuthorizationDenied;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Access\ResourceScope;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\GoodsReceiptId;

final readonly class GoodsReceiptQueryService
{
    public function __construct(private GoodsReceiptRepository $receipts, private AuthorizationService $authorization, private GoodsReceiptViewFactory $views) {}

    public function get(ActorContext $actor, GoodsReceiptId $id): GoodsReceiptView
    {
        $receipt = $this->receipts->get($actor->organizationId(), $id);
        $this->authorize($actor, $receipt);

        return $this->views->create($receipt);
    }

    /** @return list<GoodsReceiptView> */
    public function list(ActorContext $actor): array
    {
        $views = [];
        foreach ($this->receipts->findAll($actor->organizationId()) as $receipt) {
            try {
                $this->authorize($actor, $receipt);
            } catch (AuthorizationDenied) {
                continue;
            }
            $views[] = $this->views->create($receipt);
        }

        return $views;
    }

    private function authorize(ActorContext $actor, GoodsReceipt $receipt): void
    {
        $this->authorization->authorize($actor, PermissionCode::GoodsReceiptRead, ResourceScope::store($actor->organizationId(), $receipt->storeId()));
    }
}
