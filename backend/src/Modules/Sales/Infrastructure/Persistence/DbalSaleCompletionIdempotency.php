<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Infrastructure\Persistence;

use Doctrine\DBAL\Connection;
use Zandu\Modules\Sales\Application\Contract\SaleCompletionIdempotency;
use Zandu\SharedKernel\Identity\SaleId;

final readonly class DbalSaleCompletionIdempotency implements SaleCompletionIdempotency
{
    public function __construct(private Connection $connection) {}
    public function wasCompleted(SaleId $saleId, string $key): bool
    {
        return false !== $this->connection->fetchOne("SELECT 1 FROM sales.sale_completion_keys WHERE organization_id = NULLIF(current_setting('app.organization_id', true), '')::uuid AND sale_id = ? AND idempotency_key = ?", [$saleId->toString(), $key]);
    }
    public function markCompleted(SaleId $saleId, string $key): void
    {
        $this->connection->executeStatement("INSERT INTO sales.sale_completion_keys (organization_id, sale_id, idempotency_key, completed_at) VALUES (NULLIF(current_setting('app.organization_id', true), '')::uuid, ?, ?, NOW()) ON CONFLICT (organization_id, sale_id, idempotency_key) DO NOTHING", [$saleId->toString(), $key]);
    }
}
