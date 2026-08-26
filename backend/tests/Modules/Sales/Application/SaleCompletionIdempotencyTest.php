<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Sales\Application;

use PHPUnit\Framework\TestCase;
use Zandu\Modules\Sales\Application\InMemorySaleCompletionIdempotency;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Identity\SaleId;

final class SaleCompletionIdempotencyTest extends TestCase
{
    public function testCompletionKeyIsReplayablePerSale(): void
    {
        $store = new InMemorySaleCompletionIdempotency();
        $sale = SaleId::fromString('0198ef01-1111-7111-8111-111111111111', new SymfonyUuidFactory());
        self::assertFalse($store->wasCompleted($sale, 'checkout-1'));
        $store->markCompleted($sale, 'checkout-1');
        self::assertTrue($store->wasCompleted($sale, 'checkout-1'));
        self::assertFalse($store->wasCompleted($sale, 'checkout-2'));
    }
}
