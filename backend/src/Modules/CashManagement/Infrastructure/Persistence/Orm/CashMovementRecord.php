<?php

declare(strict_types=1);

namespace Zandu\Modules\CashManagement\Infrastructure\Persistence\Orm;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Zandu\Modules\CashManagement\Domain\CashMovement\CashMovement;

#[ORM\Entity]#[ORM\Table(name: 'cash_movement', schema: 'cash_management')]
final class CashMovementRecord
{
    private function __construct(#[ORM\Id]#[ORM\Column(type: 'guid')]private string $id, #[ORM\Column(type: 'guid')]private string $organizationId, #[ORM\Column(type: 'guid')]private string $storeId, #[ORM\Column(type: 'guid')]private string $cashSessionId, #[ORM\Column(length: 32)]private string $type, #[ORM\Column(type: 'decimal', precision: 30, scale: 12)]private string $amount, #[ORM\Column(length: 3)]private string $currency, #[ORM\Column(length: 32)]private string $sourceType, #[ORM\Column(type: 'guid', nullable: true)]private ?string $sourceReferenceId, #[ORM\Column(length: 500, nullable: true)]private ?string $reason, #[ORM\Column(type: 'guid', nullable: true)]private ?string $performedBy, #[ORM\Column(type: 'datetimetz_immutable')]private DateTimeImmutable $occurredAt) {}public static function fromAggregate(CashMovement $m): self
    {
        return new self($m->id()->toString(), $m->organizationId()->toString(), $m->storeId()->toString(), $m->sessionId()->toString(), $m->type()->value, $m->amount()->amount()->toString(), $m->amount()->currency()->code(), 'MANUAL', $m->sourceReference(), $m->reason(), $m->performedBy()->toString(), $m->occurredAt());
    }public function sourceType(): string
    {
        return $this->sourceType;
    }public function id(): string
    {
        return $this->id;
    }public function organizationId(): string
    {
        return $this->organizationId;
    }public function storeId(): string
    {
        return $this->storeId;
    }public function cashSessionId(): string
    {
        return $this->cashSessionId;
    }public function type(): string
    {
        return $this->type;
    }public function amount(): string
    {
        return $this->amount;
    }public function currency(): string
    {
        return $this->currency;
    }public function sourceReferenceId(): ?string
    {
        return $this->sourceReferenceId;
    }public function reason(): ?string
    {
        return $this->reason;
    }public function performedBy(): ?string
    {
        return $this->performedBy;
    }public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
