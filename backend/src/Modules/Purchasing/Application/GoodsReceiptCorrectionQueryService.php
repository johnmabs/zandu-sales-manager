<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Application;

use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Purchasing\Domain\GoodsReceipt\GoodsReceiptRepository;
use Zandu\Modules\Purchasing\Domain\GoodsReceiptCorrection\GoodsReceiptCorrectionRepository;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Access\ResourceScope;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\GoodsReceiptCorrectionId;

final readonly class GoodsReceiptCorrectionQueryService
{
    public function __construct(private GoodsReceiptCorrectionRepository $corrections, private GoodsReceiptRepository $receipts, private AuthorizationService $authorization, private GoodsReceiptCorrectionViewFactory $views) {}

    public function get(ActorContext $actor, GoodsReceiptCorrectionId $id): GoodsReceiptCorrectionView
    {
        $correction = $this->corrections->get($actor->organizationId(), $id);
        $receipt = $this->receipts->get($actor->organizationId(), $correction->goodsReceiptId());
        $this->authorization->authorize($actor, PermissionCode::GoodsReceiptRead, ResourceScope::store($actor->organizationId(), $receipt->storeId()));

        return $this->views->create($correction);
    }
}
