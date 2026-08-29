<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Application\CreateGoodsReceiptCorrection;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\GoodsReceiptId;

final readonly class CreateGoodsReceiptCorrection
{
    /** @param list<GoodsReceiptCorrectionInput> $lines */
    public function __construct(public GoodsReceiptId $goodsReceiptId, public string $reason, public array $lines, public ActorContext $actorContext) {}
}
