<?php

declare(strict_types=1);

namespace Zandu\Modules\CashManagement\Domain\CashRegister;

use DateTimeImmutable;
use InvalidArgumentException;
use LogicException;
use Zandu\SharedKernel\Identity\{ActorId,CashRegisterId,OrganizationId,StoreId};

final class CashRegister
{
    private function __construct(private readonly CashRegisterId $id, private readonly OrganizationId $organizationId, private readonly StoreId $storeId, private string $code, private string $name, private CashRegisterStatus $status, private readonly DateTimeImmutable $createdAt, private readonly ActorId $createdBy, private ?DateTimeImmutable $updatedAt, private ?ActorId $updatedBy, private int $version)
    {
        self::valid($code, $name);
        if ($version < 1) {
            throw new InvalidArgumentException('Cash register version must be positive.');
        }
    }
    public static function create(CashRegisterId $id, OrganizationId $organizationId, StoreId $storeId, string $code, string $name, ActorId $actor, DateTimeImmutable $at): self
    {
        return new self($id, $organizationId, $storeId, trim($code), trim($name), CashRegisterStatus::Active, $at, $actor, null, null, 1);
    }
    public static function reconstitute(CashRegisterId $id, OrganizationId $organizationId, StoreId $storeId, string $code, string $name, CashRegisterStatus $status, DateTimeImmutable $createdAt, ActorId $createdBy, ?DateTimeImmutable $updatedAt, ?ActorId $updatedBy, int $version): self
    {
        return new self($id, $organizationId, $storeId, $code, $name, $status, $createdAt, $createdBy, $updatedAt, $updatedBy, $version);
    }
    public function update(string $code, string $name, ActorId $actor, DateTimeImmutable $at): void
    {
        $this->requireMutable();
        self::valid($code, $name);
        $this->code = trim($code);
        $this->name = trim($name);
        $this->updatedAt = $at;
        $this->updatedBy = $actor;
        ++$this->version;
    }
    public function activate(): void
    {
        if (CashRegisterStatus::Archived === $this->status) {
            throw new LogicException('Archived cash register cannot be activated.');
        }$this->status = CashRegisterStatus::Active;
        ++$this->version;
    }
    public function deactivate(): void
    {
        if (CashRegisterStatus::Archived === $this->status) {
            throw new LogicException('Archived cash register cannot be deactivated.');
        }$this->status = CashRegisterStatus::Inactive;
        ++$this->version;
    }
    public function archive(): void
    {
        $this->status = CashRegisterStatus::Archived;
        ++$this->version;
    }
    private function requireMutable(): void
    {
        if (CashRegisterStatus::Archived === $this->status) {
            throw new LogicException('Archived cash register cannot be updated.');
        }
    }
    private static function valid(string $code, string $name): void
    {
        if ('' === trim($code) || strlen(trim($code)) > 32) {
            throw new InvalidArgumentException('Cash register code is invalid.');
        }if ('' === trim($name) || strlen(trim($name)) > 160) {
            throw new InvalidArgumentException('Cash register name is invalid.');
        }
    }
    public function id(): CashRegisterId
    {
        return $this->id;
    } public function organizationId(): OrganizationId
    {
        return $this->organizationId;
    } public function storeId(): StoreId
    {
        return $this->storeId;
    } public function code(): string
    {
        return $this->code;
    } public function name(): string
    {
        return $this->name;
    } public function status(): CashRegisterStatus
    {
        return $this->status;
    } public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    } public function createdBy(): ActorId
    {
        return $this->createdBy;
    } public function updatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    } public function updatedBy(): ?ActorId
    {
        return $this->updatedBy;
    } public function version(): int
    {
        return $this->version;
    }
}
