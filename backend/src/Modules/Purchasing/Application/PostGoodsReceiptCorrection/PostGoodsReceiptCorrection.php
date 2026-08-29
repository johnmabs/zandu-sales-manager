<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Application\PostGoodsReceiptCorrection;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\GoodsReceiptCorrectionId;

final readonly class PostGoodsReceiptCorrection
{
    public function __construct(public GoodsReceiptCorrectionId $correctionId, public ActorContext $actorContext, public string $commandId = '') {}
}
