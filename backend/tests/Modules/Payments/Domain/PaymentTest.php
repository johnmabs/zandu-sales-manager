<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Payments\Domain;

use DateTimeImmutable;
use LogicException;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\Payments\Domain\{Payment,PaymentStatus};
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Identity\{ActorId,OrganizationId,PaymentId,SaleId};
use Zandu\SharedKernel\Money\{Currency,Money};

final class PaymentTest extends TestCase
{
    public function testCashSalePaymentIsConfirmedOnce(): void
    {
        $f = new SymfonyUuidFactory();
        $payment = Payment::createCashSale(PaymentId::fromString('0198eb01-1111-7111-8111-111111111111', $f), OrganizationId::fromString('0198eb02-1111-7111-8111-111111111111', $f), SaleId::fromString('0198eb03-1111-7111-8111-111111111111', $f), Money::fromString('10', Currency::fromCode('XAF'), new BrickDecimalFactory()), ActorId::fromString('0198eb04-1111-7111-8111-111111111111', $f), new DateTimeImmutable());
        $payment->confirm(new DateTimeImmutable());
        self::assertSame(PaymentStatus::Confirmed, $payment->status());
        $this->expectException(LogicException::class);
        $payment->confirm(new DateTimeImmutable());
    }
}
