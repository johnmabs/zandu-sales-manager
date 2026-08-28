<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Domain\Supplier;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use Zandu\Modules\Purchasing\Domain\PurchasingRuleViolation;
use Zandu\Modules\Purchasing\Domain\Supplier\Event\SupplierActivated;
use Zandu\Modules\Purchasing\Domain\Supplier\Event\SupplierArchived;
use Zandu\Modules\Purchasing\Domain\Supplier\Event\SupplierCreated;
use Zandu\Modules\Purchasing\Domain\Supplier\Event\SupplierDeactivated;
use Zandu\Modules\Purchasing\Domain\Supplier\Event\SupplierEvent;
use Zandu\Modules\Purchasing\Domain\Supplier\Event\SupplierUpdated;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\SupplierId;

final class Supplier
{
    /** @var list<SupplierEvent> */
    private array $recordedEvents = [];

    private function __construct(
        private readonly SupplierId $id,
        private readonly OrganizationId $organizationId,
        private SupplierName $name,
        private ?string $phone,
        private ?string $email,
        private ?string $address,
        private ?string $notes,
        private SupplierStatus $status,
        private readonly DateTimeImmutable $createdAt,
        private readonly ActorId $createdBy,
        private ?DateTimeImmutable $updatedAt,
        private ?ActorId $updatedBy,
        private int $version,
    ) {
        if ($version < 1) {
            throw new InvalidArgumentException('Supplier version must be positive.');
        }
        $this->phone = self::optional($phone, 64, 'phone');
        $this->email = self::normalizedEmail($email);
        $this->address = self::optional($address, 500, 'address');
        $this->notes = self::optional($notes, 2000, 'notes');
    }

    public static function create(
        SupplierId $id,
        OrganizationId $organizationId,
        SupplierName $name,
        ?string $phone,
        ?string $email,
        ?string $address,
        ?string $notes,
        ActorId $actorId,
        DateTimeImmutable $occurredAt,
    ): self {
        $occurredAt = self::utc($occurredAt);
        $supplier = new self(
            $id,
            $organizationId,
            $name,
            $phone,
            $email,
            $address,
            $notes,
            SupplierStatus::Active,
            $occurredAt,
            $actorId,
            null,
            null,
            1,
        );
        $supplier->recordedEvents[] = new SupplierCreated($organizationId, $id, $actorId, $occurredAt);

        return $supplier;
    }

    public static function reconstitute(
        SupplierId $id,
        OrganizationId $organizationId,
        SupplierName $name,
        ?string $phone,
        ?string $email,
        ?string $address,
        ?string $notes,
        SupplierStatus $status,
        DateTimeImmutable $createdAt,
        ActorId $createdBy,
        ?DateTimeImmutable $updatedAt,
        ?ActorId $updatedBy,
        int $version,
    ): self {
        return new self(
            $id,
            $organizationId,
            $name,
            $phone,
            $email,
            $address,
            $notes,
            $status,
            self::utc($createdAt),
            $createdBy,
            null !== $updatedAt ? self::utc($updatedAt) : null,
            $updatedBy,
            $version,
        );
    }

    public function update(
        SupplierName $name,
        ?string $phone,
        ?string $email,
        ?string $address,
        ?string $notes,
        ActorId $actorId,
        DateTimeImmutable $occurredAt,
    ): void {
        $this->requireNotArchived('SUPPLIER_ARCHIVED', 'An archived supplier cannot be updated.');
        $this->name = $name;
        $this->phone = self::optional($phone, 64, 'phone');
        $this->email = self::normalizedEmail($email);
        $this->address = self::optional($address, 500, 'address');
        $this->notes = self::optional($notes, 2000, 'notes');
        $occurredAt = $this->changedBy($actorId, $occurredAt);
        $this->recordedEvents[] = new SupplierUpdated($this->organizationId, $this->id, $actorId, $occurredAt);
    }

    public function activate(ActorId $actorId, DateTimeImmutable $occurredAt): void
    {
        $this->requireStatus(SupplierStatus::Inactive, 'SUPPLIER_NOT_INACTIVE', 'Only an inactive supplier can be activated.');
        $this->status = SupplierStatus::Active;
        $occurredAt = $this->changedBy($actorId, $occurredAt);
        $this->recordedEvents[] = new SupplierActivated($this->organizationId, $this->id, $actorId, $occurredAt);
    }

    public function deactivate(ActorId $actorId, DateTimeImmutable $occurredAt): void
    {
        $this->requireStatus(SupplierStatus::Active, 'SUPPLIER_NOT_ACTIVE', 'Only an active supplier can be deactivated.');
        $this->status = SupplierStatus::Inactive;
        $occurredAt = $this->changedBy($actorId, $occurredAt);
        $this->recordedEvents[] = new SupplierDeactivated($this->organizationId, $this->id, $actorId, $occurredAt);
    }

    public function archive(ActorId $actorId, DateTimeImmutable $occurredAt): void
    {
        $this->requireNotArchived('SUPPLIER_ALREADY_ARCHIVED', 'Supplier is already archived.');
        $this->status = SupplierStatus::Archived;
        $occurredAt = $this->changedBy($actorId, $occurredAt);
        $this->recordedEvents[] = new SupplierArchived($this->organizationId, $this->id, $actorId, $occurredAt);
    }

    public function ensureUsable(): void
    {
        $this->requireStatus(SupplierStatus::Active, 'SUPPLIER_NOT_ACTIVE', 'Only an active supplier can be used.');
    }

    /** @return list<SupplierEvent> */
    public function releaseEvents(): array
    {
        $events = $this->recordedEvents;
        $this->recordedEvents = [];

        return $events;
    }

    public function id(): SupplierId
    {
        return $this->id;
    }
    public function organizationId(): OrganizationId
    {
        return $this->organizationId;
    }
    public function name(): SupplierName
    {
        return $this->name;
    }
    public function phone(): ?string
    {
        return $this->phone;
    }
    public function email(): ?string
    {
        return $this->email;
    }
    public function address(): ?string
    {
        return $this->address;
    }
    public function notes(): ?string
    {
        return $this->notes;
    }
    public function status(): SupplierStatus
    {
        return $this->status;
    }
    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
    public function createdBy(): ActorId
    {
        return $this->createdBy;
    }
    public function updatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }
    public function updatedBy(): ?ActorId
    {
        return $this->updatedBy;
    }
    public function version(): int
    {
        return $this->version;
    }

    private function changedBy(ActorId $actorId, DateTimeImmutable $occurredAt): DateTimeImmutable
    {
        $occurredAt = self::utc($occurredAt);
        $this->updatedAt = $occurredAt;
        $this->updatedBy = $actorId;
        ++$this->version;

        return $occurredAt;
    }

    private function requireStatus(SupplierStatus $status, string $code, string $message): void
    {
        if ($this->status !== $status) {
            throw PurchasingRuleViolation::with($code, $message);
        }
    }

    private function requireNotArchived(string $code, string $message): void
    {
        if (SupplierStatus::Archived === $this->status) {
            throw PurchasingRuleViolation::with($code, $message);
        }
    }

    private static function normalizedEmail(?string $value): ?string
    {
        $value = self::optional($value, 254, 'email');
        if (null !== $value && false === filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Supplier email must be valid.');
        }

        return null !== $value ? mb_strtolower($value) : null;
    }

    private static function optional(?string $value, int $maxLength, string $field): ?string
    {
        if (null === $value) {
            return null;
        }
        $value = trim($value);
        if ('' === $value) {
            return null;
        }
        if (mb_strlen($value) > $maxLength) {
            throw new InvalidArgumentException(sprintf('Supplier %s cannot exceed %d characters.', $field, $maxLength));
        }

        return $value;
    }

    private static function utc(DateTimeImmutable $dateTime): DateTimeImmutable
    {
        return $dateTime->setTimezone(new DateTimeZone('UTC'));
    }
}
