<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Domain\GoodsReceipt\Event;

use DateTimeImmutable;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\GoodsReceiptId;
use Zandu\SharedKernel\Identity\OrganizationId;

abstract readonly class GoodsReceiptEvent
{
    public function __construct(
        private OrganizationId $organizationId,
        private GoodsReceiptId $goodsReceiptId,
        private ActorId $actorId,
        private DateTimeImmutable $occurredAt,
    ) {}

    public function organizationId(): OrganizationId
    {
        return $this->organizationId;
    }
    public function goodsReceiptId(): GoodsReceiptId
    {
        return $this->goodsReceiptId;
    }
    public function actorId(): ActorId
    {
        return $this->actorId;
    }
    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
