<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\CashManagement\Domain;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\CashManagement\Domain\CashRegister\{CashRegister,CashRegisterStatus};
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Identity\{ActorId,CashRegisterId,OrganizationId,StoreId};

final class CashRegisterTest extends TestCase
{
    public function testLifecycle(): void
    {
        $f = new SymfonyUuidFactory();
        $r = CashRegister::create(CashRegisterId::fromString('0198d501-1111-7111-8111-111111111111', $f), OrganizationId::fromString('0198d502-1111-7111-8111-111111111111', $f), StoreId::fromString('0198d503-1111-7111-8111-111111111111', $f), 'REG', 'Main', ActorId::fromString('0198d504-1111-7111-8111-111111111111', $f), new DateTimeImmutable());
        $r->deactivate();
        self::assertSame(CashRegisterStatus::Inactive, $r->status());
        $r->activate();
        $r->archive();
        self::assertSame(CashRegisterStatus::Archived, $r->status());
    }
}
