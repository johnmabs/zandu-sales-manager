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
        self::assertTrue($store->claim($sale, 'checkout-1', 'hash-1'));
        self::assertFalse($store->claim($sale, 'checkout-1', 'hash-1'));
        self::assertTrue($store->claim($sale, 'checkout-2', 'hash-2'));
    }

    public function testReusingAKeyForAnotherPayloadIsRejected(): void
    {
        $store = new InMemorySaleCompletionIdempotency();
        $sale = SaleId::fromString('0198ef01-1111-7111-8111-111111111111', new SymfonyUuidFactory());
        $store->claim($sale, 'checkout-1', 'hash-1');

        $this->expectException(\LogicException::class);
        $store->claim($sale, 'checkout-1', 'hash-2');
    }
}
