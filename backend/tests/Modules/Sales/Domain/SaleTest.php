<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Sales\Domain;

use DateTimeImmutable;
use LogicException;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\Sales\Domain\{Sale,SaleStatus};
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Context\{ActorContext,ActorType};
use Zandu\SharedKernel\Identity\{ActorId,SaleId,StoreId};
use Zandu\SharedKernel\Messaging\CorrelationId;
use Zandu\SharedKernel\Money\{Currency,Money};

final class SaleTest extends TestCase
{
    public function testDraftCannotBeCompletedWithoutLines(): void
    {
        $sale = $this->sale();
        $this->expectException(LogicException::class);
        $sale->complete($this->actor(), new DateTimeImmutable());
    }

    public function testSaleCanBeCompletedOnceLineExistsAndCannotBeEdited(): void
    {
        $sale = $this->sale();
        $sale->addLine();
        $sale->complete($this->actor(), new DateTimeImmutable());
        self::assertSame(SaleStatus::Completed, $sale->status());
        $this->expectException(LogicException::class);
        $sale->addLine();
    }

    public function testCompletedSaleCannotBeCancelled(): void
    {
        $sale = $this->sale();
        $sale->addLine();
        $sale->complete($this->actor(), new DateTimeImmutable());
        $this->expectException(LogicException::class);
        $sale->cancel($this->actor(), new DateTimeImmutable());
    }

    private function sale(): Sale
    {
        $f = new SymfonyUuidFactory();
        return Sale::create(SaleId::fromString('0198ea01-1111-7111-8111-111111111111', $f), '0198ea02-1111-7111-8111-111111111111', StoreId::fromString('0198ea03-1111-7111-8111-111111111111', $f), 'XAF', Money::fromString('0', Currency::fromCode('XAF'), new BrickDecimalFactory()), $this->actor(), new DateTimeImmutable());
    }

    private function actor(): ActorContext
    {
        $f = new SymfonyUuidFactory();
        return new ActorContext(ActorId::fromString('0198ea04-1111-7111-8111-111111111111', $f), \Zandu\SharedKernel\Identity\OrganizationId::fromString('0198ea02-1111-7111-8111-111111111111', $f), ActorType::User, CorrelationId::fromString('0198ea05-1111-7111-8111-111111111111', $f), new DateTimeImmutable());
    }
}
