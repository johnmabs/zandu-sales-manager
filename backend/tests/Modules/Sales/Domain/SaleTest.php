<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Sales\Domain;

use DateTimeImmutable;
use LogicException;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\Sales\Domain\{Sale,SaleLine,SaleStatus};
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Context\{ActorContext,ActorType};
use Zandu\SharedKernel\Identity\{ActorId,OrganizationId,ProductId,ProductPackagingId,SaleId,SaleLineId,StoreId,UnitOfMeasureId};
use Zandu\SharedKernel\Messaging\CorrelationId;
use Zandu\SharedKernel\Money\{Currency,Money};
use Zandu\SharedKernel\Quantity\Quantity;

final class SaleTest extends TestCase
{
    public function testDraftCannotBeCompletedWithoutLines(): void
    {
        $sale = $this->sale();
        $this->expectException(LogicException::class);
        $sale->complete($this->actor(), new DateTimeImmutable(), '2026-08-27');
    }

    public function testSaleCanBeCompletedOnceLineExistsAndCannotBeEdited(): void
    {
        $sale = $this->sale();
        $sale->addLine($this->line($sale));
        $sale->complete($this->actor(), new DateTimeImmutable(), '2026-08-27');
        self::assertSame(SaleStatus::Completed, $sale->status());
        $this->expectException(LogicException::class);
        $sale->addLine($this->line($sale));
    }

    public function testCompletedSaleCannotBeCancelled(): void
    {
        $sale = $this->sale();
        $sale->addLine($this->line($sale));
        $sale->complete($this->actor(), new DateTimeImmutable(), '2026-08-27');
        $this->expectException(LogicException::class);
        $sale->cancel($this->actor(), new DateTimeImmutable());
    }

    public function testDraftLineCanBeReplacedAndRemovedWithExactTotalRecalculation(): void
    {
        $sale = $this->sale();
        $original = $this->line($sale);
        $sale->addLine($original);
        $replacement = $this->line($sale, '2', '20');
        $sale->replaceLine($replacement);

        self::assertSame('20', $sale->total()->amount()->toString());
        self::assertSame('2', $sale->line($replacement->id())->enteredQuantity()->toString());

        $sale->removeLine($replacement->id());
        self::assertSame(0, $sale->lineCount());
        self::assertSame('0', $sale->total()->amount()->toString());
    }

    public function testCancelledSaleCannotBeCompletedOrEdited(): void
    {
        $sale = $this->sale();
        $sale->addLine($this->line($sale));
        $sale->cancel($this->actor(), new DateTimeImmutable());

        self::assertSame(SaleStatus::Cancelled, $sale->status());
        $this->expectException(LogicException::class);
        $sale->complete($this->actor(), new DateTimeImmutable(), '2026-08-27');
    }

    private function sale(): Sale
    {
        $f = new SymfonyUuidFactory();
        return Sale::create(SaleId::fromString('0198ea01-1111-7111-8111-111111111111', $f), OrganizationId::fromString('0198ea02-1111-7111-8111-111111111111', $f), StoreId::fromString('0198ea03-1111-7111-8111-111111111111', $f), 'XAF', Money::fromString('0', Currency::fromCode('XAF'), new BrickDecimalFactory()), $this->actor(), new DateTimeImmutable());
    }

    private function actor(): ActorContext
    {
        $f = new SymfonyUuidFactory();
        return new ActorContext(ActorId::fromString('0198ea04-1111-7111-8111-111111111111', $f), \Zandu\SharedKernel\Identity\OrganizationId::fromString('0198ea02-1111-7111-8111-111111111111', $f), ActorType::User, CorrelationId::fromString('0198ea05-1111-7111-8111-111111111111', $f), new DateTimeImmutable());
    }

    private function line(Sale $sale, string $quantity = '1', string $total = '10'): SaleLine
    {
        $f = new SymfonyUuidFactory();
        $q = Quantity::fromString($quantity, new BrickDecimalFactory());
        $factor = Quantity::fromString('1', new BrickDecimalFactory());
        $m = Money::fromString($total, Currency::fromCode('XAF'), new BrickDecimalFactory());
        return new SaleLine(SaleLineId::fromString('0198ea06-1111-7111-8111-111111111111', $f), $sale->id(), ProductId::fromString('0198ea07-1111-7111-8111-111111111111', $f), ProductPackagingId::fromString('0198ea08-1111-7111-8111-111111111111', $f), 'SKU', 'Product', 'UNIT', 'Unit', UnitOfMeasureId::fromString('0198ea09-1111-7111-8111-111111111111', $f), $q, $factor, $q, Money::fromString('10', Currency::fromCode('XAF'), new BrickDecimalFactory()), null, null, Money::fromString('0', Currency::fromCode('XAF'), new BrickDecimalFactory()), $m, Money::fromString('0', Currency::fromCode('XAF'), new BrickDecimalFactory()), $m, $m);
    }
}
