<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Infrastructure\Persistence;

use Doctrine\DBAL\Connection;
use Zandu\Modules\Sales\Application\Contract\SaleCompletionIdempotency;
use Zandu\Modules\Sales\Domain\SalesRuleViolation;
use Zandu\SharedKernel\Identity\SaleId;

final readonly class DbalSaleCompletionIdempotency implements SaleCompletionIdempotency
{
    public function __construct(private Connection $connection) {}
    public function claim(SaleId $saleId, string $key, string $payloadHash): bool
    {
        $affected = $this->connection->executeStatement(
            "INSERT INTO sales.sale_completion_keys (organization_id, sale_id, idempotency_key, payload_hash, completed_at) VALUES (NULLIF(current_setting('app.organization_id', true), '')::uuid, ?, ?, ?, NOW()) ON CONFLICT (organization_id, sale_id, idempotency_key) DO NOTHING",
            [$saleId->toString(), $key, $payloadHash],
        );
        if (1 === $affected) {
            return true;
        }
        $existingHash = $this->connection->fetchOne(
            "SELECT payload_hash FROM sales.sale_completion_keys WHERE organization_id = NULLIF(current_setting('app.organization_id', true), '')::uuid AND sale_id = ? AND idempotency_key = ?",
            [$saleId->toString(), $key],
        );
        if (!is_string($existingHash) || !hash_equals($existingHash, $payloadHash)) {
            throw SalesRuleViolation::with('IDEMPOTENCY_CONFLICT', 'Idempotency key was already used with a different payload.');
        }

        return false;
    }
}
