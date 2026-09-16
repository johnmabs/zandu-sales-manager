<?php

declare(strict_types=1);

namespace Zandu\Modules\CashManagement\Domain\CashSession;

use DateTimeImmutable;
use LogicException;
use Zandu\SharedKernel\Identity\{ActorId,CashRegisterId,CashSessionId,OrganizationId,StoreId};
use Zandu\SharedKernel\Money\Money;
use Zandu\SharedKernel\Versioning\{TracksAggregateVersion, VersionedAggregate};

final class CashSession implements VersionedAggregate
{
    use TracksAggregateVersion;

    private function __construct(private readonly CashSessionId $id, private readonly OrganizationId $organizationId, private readonly StoreId $storeId, private readonly CashRegisterId $cashRegisterId, private readonly ActorId $cashierId, private Money $openingBalance, private DateTimeImmutable $openedAt, private CashSessionStatus $status, private ?Money $countedClosingBalance, private ?Money $expectedClosingBalance, private ?Money $discrepancy, private ?DateTimeImmutable $closedAt, private ?ActorId $closedBy, private int $version)
    {
        $this->assertValidVersion();
        if ($openingBalance->amount()->isNegative()) {
            throw new \InvalidArgumentException('Opening balance cannot be negative.');
        }
    }
    public static function open(CashSessionId $id, OrganizationId $organizationId, StoreId $storeId, CashRegisterId $registerId, ActorId $cashier, Money $opening, DateTimeImmutable $at): self
    {
        return new self($id, $organizationId, $storeId, $registerId, $cashier, $opening, $at, CashSessionStatus::Open, null, null, null, null, null, 1);
    }
    public static function reconstitute(CashSessionId $id, OrganizationId $organizationId, StoreId $storeId, CashRegisterId $registerId, ActorId $cashier, Money $opening, DateTimeImmutable $openedAt, CashSessionStatus $status, ?Money $counted, ?Money $expected, ?Money $discrepancy, ?DateTimeImmutable $closedAt, ?ActorId $closedBy, int $version): self
    {
        return new self($id, $organizationId, $storeId, $registerId, $cashier, $opening, $openedAt, $status, $counted, $expected, $discrepancy, $closedAt, $closedBy, $version);
    }
    public function close(Money $counted, Money $expected, ActorId $actor, DateTimeImmutable $at): void
    {
        if (CashSessionStatus::Closed === $this->status) {
            throw new LogicException('Cash session is already closed.');
        }if (!$counted->currency()->equals($this->openingBalance->currency()) || !$expected->currency()->equals($this->openingBalance->currency())) {
            throw new LogicException('Cash session currencies must match.');
        }$this->countedClosingBalance = $counted;
        $this->expectedClosingBalance = $expected;
        $this->discrepancy = $counted->subtract($expected);
        $this->closedAt = $at;
        $this->closedBy = $actor;
        $this->status = CashSessionStatus::Closed;
        $this->advanceVersion();
    }
    public function calculateExpectedBalance(Money $netMovement): Money
    {
        if (!$netMovement->currency()->equals($this->openingBalance->currency())) {
            throw new LogicException('Cash movement currency must match session currency.');
        } return $this->openingBalance->add($netMovement);
    }
    public function id(): CashSessionId
    {
        return $this->id;
    }public function organizationId(): OrganizationId
    {
        return $this->organizationId;
    }public function storeId(): StoreId
    {
        return $this->storeId;
    }public function cashRegisterId(): CashRegisterId
    {
        return $this->cashRegisterId;
    }public function cashierId(): ActorId
    {
        return $this->cashierId;
    }public function openingBalance(): Money
    {
        return $this->openingBalance;
    }public function openedAt(): DateTimeImmutable
    {
        return $this->openedAt;
    }public function status(): CashSessionStatus
    {
        return $this->status;
    }public function countedClosingBalance(): ?Money
    {
        return $this->countedClosingBalance;
    }public function expectedClosingBalance(): ?Money
    {
        return $this->expectedClosingBalance;
    }public function discrepancy(): ?Money
    {
        return $this->discrepancy;
    }public function closedAt(): ?DateTimeImmutable
    {
        return $this->closedAt;
    }public function closedBy(): ?ActorId
    {
        return $this->closedBy;
    }
}
