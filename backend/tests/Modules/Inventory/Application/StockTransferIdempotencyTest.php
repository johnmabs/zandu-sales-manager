<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Inventory\Application;

use PHPUnit\Framework\TestCase;
use Zandu\Modules\Inventory\Application\Contract\StockTransferPhase;
use Zandu\Modules\Inventory\Application\InMemoryStockTransferIdempotency;
use Zandu\Modules\Inventory\Domain\InventoryRuleViolation;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Identity\StockTransferId;

final class StockTransferIdempotencyTest extends TestCase
{
    public function testIdenticalReplayIsAcceptedAndPayloadMismatchIsRejectedPerPhase(): void
    {
        $idempotency = new InMemoryStockTransferIdempotency();
        $transferId = StockTransferId::fromString('0199f300-0000-7000-8000-000000000001', new SymfonyUuidFactory());

        self::assertTrue($idempotency->claim($transferId, StockTransferPhase::TransferOut, 'command-1', hash('sha256', 'line=3')));
        self::assertFalse($idempotency->claim($transferId, StockTransferPhase::TransferOut, 'command-1', hash('sha256', 'line=3')));
        self::assertTrue($idempotency->claim($transferId, StockTransferPhase::TransferIn, 'command-1', hash('sha256', 'line=2')));

        try {
            $idempotency->claim($transferId, StockTransferPhase::TransferOut, 'command-1', hash('sha256', 'line=4'));
            self::fail('A reused command identifier with another payload must fail.');
        } catch (InventoryRuleViolation $exception) {
            self::assertSame('IDEMPOTENCY_CONFLICT', $exception->errorCode());
        }
    }
}
