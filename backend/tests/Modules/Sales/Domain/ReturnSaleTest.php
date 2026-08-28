<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Sales\Domain;

use DateTimeImmutable;
use LogicException;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\Sales\Domain\{ReturnAmounts, ReturnSale, ReturnSaleLine, ReturnSaleStatus, SaleLine, SalesRuleViolation};
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Context\{ActorContext, ActorType};
use Zandu\SharedKernel\Identity\{ActorId, OrganizationId, ProductId, ProductPackagingId, ReturnSaleId, ReturnSaleLineId, SaleId, SaleLineId, StoreId, UnitOfMeasureId};
use Zandu\SharedKernel\Messaging\CorrelationId;
use Zandu\SharedKernel\Money\{Currency, Money};
use Zandu\SharedKernel\Quantity\Quantity;

final class ReturnSaleTest extends TestCase
{
    public function testItCreatesATenantOwnedDraftWithNormalizedMetadata(): void
    {
        $return = $this->returnSale('  Damaged packaging  ', new DateTimeImmutable('2026-08-28T11:00:00+01:00'));

        self::assertSame(ReturnSaleStatus::Draft, $return->status());
        self::assertSame('Damaged packaging', $return->reason());
        self::assertSame('2026-08-28T10:00:00+00:00', $return->createdAt()->format(DATE_ATOM));
        self::assertSame(1, $return->version());
        self::assertSame(0, $return->lineCount());
        self::assertNull($return->businessDate());
    }

    public function testItAddsOneOriginalSaleLineAndRejectsDuplicates(): void
    {
        $return = $this->returnSale();
        $line = $this->returnLine();
        $return->addLine($line);

        self::assertSame(1, $return->lineCount());
        self::assertSame($line, $return->line($line->id()));
        self::assertSame(2, $return->version());

        try {
            $return->addLine($this->returnLine('019a3100-0000-7000-8000-000000000099'));
            self::fail('A duplicate original sale line should be rejected.');
        } catch (SalesRuleViolation $exception) {
            self::assertSame('RETURN_LINE_ALREADY_EXISTS', $exception->errorCode());
        }
    }

    public function testItCompletesANonEmptyReturnAndBecomesImmutable(): void
    {
        $return = $this->returnSale();
        $return->addLine($this->returnLine());
        $return->complete($this->actor(), new DateTimeImmutable('2026-08-28T23:30:00+01:00'), '2026-08-28', $this->lineAmounts($return));

        self::assertSame(ReturnSaleStatus::Completed, $return->status());
        self::assertSame('2026-08-28', $return->businessDate());
        self::assertTrue($return->completedBy()?->equals($this->actor()->actorId()));
        self::assertSame('2026-08-28T22:30:00+00:00', $return->completedAt()?->format(DATE_ATOM));
        self::assertSame(3, $return->version());

        $this->expectException(SalesRuleViolation::class);
        $return->addLine($this->returnLine('019a3100-0000-7000-8000-000000000098'));
    }

    public function testItRejectsCompletionWithoutLines(): void
    {
        $return = $this->returnSale();

        try {
            $return->complete($this->actor(), new DateTimeImmutable(), '2026-08-28', []);
            self::fail('An empty return should not be completed.');
        } catch (SalesRuleViolation $exception) {
            self::assertSame('RETURN_EMPTY', $exception->errorCode());
        }
        self::assertSame(ReturnSaleStatus::Draft, $return->status());
        self::assertSame(1, $return->version());
    }

    public function testItRequiresCalculatedAmountsForEveryLine(): void
    {
        $return = $this->returnSale();
        $return->addLine($this->returnLine());

        try {
            $return->complete($this->actor(), new DateTimeImmutable(), '2026-08-28', []);
            self::fail('A return without calculated amounts should not be completed.');
        } catch (SalesRuleViolation $exception) {
            self::assertSame('RETURN_AMOUNTS_REQUIRED', $exception->errorCode());
        }
        self::assertSame(ReturnSaleStatus::Draft, $return->status());
    }

    public function testItCancelsADraftAndRejectsFurtherMutation(): void
    {
        $return = $this->returnSale();
        $return->cancel($this->actor(), new DateTimeImmutable('2026-08-28T12:00:00+01:00'));

        self::assertSame(ReturnSaleStatus::Cancelled, $return->status());
        self::assertTrue($return->cancelledBy()?->equals($this->actor()->actorId()));
        self::assertSame('2026-08-28T11:00:00+00:00', $return->cancelledAt()?->format(DATE_ATOM));
        self::assertSame(2, $return->version());

        $this->expectException(SalesRuleViolation::class);
        $return->complete($this->actor(), new DateTimeImmutable(), '2026-08-28', []);
    }

    public function testItRejectsALineFromAnotherSale(): void
    {
        $return = $this->returnSale();
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Return line belongs to another sale.');

        $return->addLine($this->returnLine(saleId: '019a3100-0000-7000-8000-000000000097'));
    }

    private function returnSale(?string $reason = null, ?DateTimeImmutable $at = null): ReturnSale
    {
        return ReturnSale::create(
            ReturnSaleId::fromString('019a3100-0000-7000-8000-000000000001', $this->uuids()),
            $this->organizationId(),
            StoreId::fromString('019a3100-0000-7000-8000-000000000002', $this->uuids()),
            $this->saleId(),
            $reason,
            $this->actor(),
            $at ?? new DateTimeImmutable('2026-08-28T10:00:00Z'),
        );
    }

    /** @return array<string, ReturnAmounts> */
    private function lineAmounts(ReturnSale $return): array
    {
        $currency = Currency::fromCode('XAF');
        $decimals = new BrickDecimalFactory();
        $zero = Money::fromString('0', $currency, $decimals);
        $half = Money::fromString('500', $currency, $decimals);
        $line = $return->lines()[0];

        return [$line->id()->toString() => new ReturnAmounts($zero, $half, $zero, $half, $half)];
    }

    private function returnLine(string $id = '019a3100-0000-7000-8000-000000000003', ?string $saleId = null): ReturnSaleLine
    {
        return new ReturnSaleLine(
            ReturnSaleLineId::fromString($id, $this->uuids()),
            $this->originalLine($saleId),
            null,
            $this->quantity('1'),
            false,
            null,
        );
    }

    private function originalLine(?string $saleId = null): SaleLine
    {
        $currency = Currency::fromCode('XAF');
        $zero = Money::fromString('0', $currency, new BrickDecimalFactory());
        $total = Money::fromString('1000', $currency, new BrickDecimalFactory());

        return new SaleLine(
            SaleLineId::fromString('019a3100-0000-7000-8000-000000000004', $this->uuids()),
            null === $saleId ? $this->saleId() : SaleId::fromString($saleId, $this->uuids()),
            ProductId::fromString('019a3100-0000-7000-8000-000000000005', $this->uuids()),
            ProductPackagingId::fromString('019a3100-0000-7000-8000-000000000006', $this->uuids()),
            'SKU',
            'Product',
            'UNIT',
            'Unit',
            UnitOfMeasureId::fromString('019a3100-0000-7000-8000-000000000007', $this->uuids()),
            $this->quantity('2'),
            $this->quantity('1'),
            $this->quantity('2'),
            Money::fromString('500', $currency, new BrickDecimalFactory()),
            null,
            null,
            $zero,
            $total,
            $zero,
            $total,
            $total,
        );
    }

    private function actor(): ActorContext
    {
        return new ActorContext(
            ActorId::fromString('019a3100-0000-7000-8000-000000000008', $this->uuids()),
            $this->organizationId(),
            ActorType::User,
            CorrelationId::fromString('019a3100-0000-7000-8000-000000000009', $this->uuids()),
            new DateTimeImmutable('2026-08-28T10:00:00Z'),
        );
    }

    private function organizationId(): OrganizationId
    {
        return OrganizationId::fromString('019a3100-0000-7000-8000-000000000010', $this->uuids());
    }

    private function saleId(): SaleId
    {
        return SaleId::fromString('019a3100-0000-7000-8000-000000000011', $this->uuids());
    }

    private function quantity(string $value): Quantity
    {
        return Quantity::fromString($value, new BrickDecimalFactory());
    }

    private function uuids(): SymfonyUuidFactory
    {
        return new SymfonyUuidFactory();
    }
}
