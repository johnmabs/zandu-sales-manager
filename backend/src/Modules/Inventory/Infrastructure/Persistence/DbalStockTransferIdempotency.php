<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Infrastructure\Persistence;

use Doctrine\DBAL\Connection;
use Zandu\Modules\Inventory\Application\Contract\{StockTransferIdempotency, StockTransferPhase};
use Zandu\Modules\Inventory\Domain\InventoryRuleViolation;
use Zandu\SharedKernel\Identity\StockTransferId;

final readonly class DbalStockTransferIdempotency implements StockTransferIdempotency
{
    public function __construct(private Connection $db) {}

    public function claim(StockTransferId $transferId, StockTransferPhase $phase, string $commandId, string $payloadHash): bool
    {
        $affected = $this->db->executeStatement(
            "INSERT INTO inventory.stock_transfer_command (organization_id,stock_transfer_id,phase,command_id,payload_hash,completed_at) VALUES (NULLIF(current_setting('app.organization_id',true),'')::uuid,?,?,?,?,NOW()) ON CONFLICT (organization_id,stock_transfer_id,phase,command_id) DO NOTHING",
            [$transferId->toString(), $phase->value, $commandId, $payloadHash],
        );
        if (1 === $affected) {
            return true;
        }
        $existingHash = $this->db->fetchOne(
            "SELECT payload_hash FROM inventory.stock_transfer_command WHERE organization_id=NULLIF(current_setting('app.organization_id',true),'')::uuid AND stock_transfer_id=? AND phase=? AND command_id=?",
            [$transferId->toString(), $phase->value, $commandId],
        );
        if (!is_string($existingHash) || !hash_equals($existingHash, $payloadHash)) {
            throw InventoryRuleViolation::with('IDEMPOTENCY_CONFLICT', 'Idempotency key was already used with a different stock transfer payload.');
        }

        return false;
    }
}
