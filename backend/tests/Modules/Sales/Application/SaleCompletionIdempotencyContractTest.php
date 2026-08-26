<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Sales\Application;

use PHPUnit\Framework\TestCase;
use Zandu\Modules\Sales\Application\Contract\SaleCompletionIdempotency;
use Zandu\Modules\Sales\Infrastructure\Persistence\DbalSaleCompletionIdempotency;

final class SaleCompletionIdempotencyContractTest extends TestCase
{
    public function testPersistentImplementationExists(): void
    {
        self::assertTrue(is_subclass_of(DbalSaleCompletionIdempotency::class, SaleCompletionIdempotency::class));
    }
}
