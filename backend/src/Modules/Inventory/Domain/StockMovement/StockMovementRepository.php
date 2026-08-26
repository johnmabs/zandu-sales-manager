<?php
declare(strict_types=1);
namespace Zandu\Modules\Inventory\Domain\StockMovement;
use Zandu\SharedKernel\Identity\{OrganizationId,StockId};
interface StockMovementRepository
{
    public function append(StockMovement $movement): void;
    /** @return list<StockMovement> */
    public function findByStock(OrganizationId $organizationId, StockId $stockId): array;
}
