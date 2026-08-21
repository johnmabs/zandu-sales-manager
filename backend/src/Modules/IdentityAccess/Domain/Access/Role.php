<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Domain\Access;

use InvalidArgumentException;
use LogicException;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\RoleId;

final class Role
{
    /** @param non-empty-list<PermissionCode> $permissions */
    private function __construct(
        private readonly RoleId $id,
        private readonly ?OrganizationId $organizationId,
        private readonly RoleCode $code,
        private readonly RoleType $type,
        private RoleStatus $status,
        private string $name,
        private ?string $description,
        private array $permissions,
        private int $version,
    ) {}

    /** @param non-empty-list<PermissionCode> $permissions */
    public static function system(RoleId $id, RoleCode $code, string $name, ?string $description, array $permissions): self
    {
        return new self($id, null, $code, RoleType::System, RoleStatus::Active, self::normalizeName($name), self::normalizeDescription($description), self::unique($permissions), 1);
    }

    /** @param non-empty-list<PermissionCode> $permissions */
    public static function custom(RoleId $id, OrganizationId $organizationId, RoleCode $code, string $name, ?string $description, array $permissions): self
    {
        if ($code->isReservedForSystemRole()) {
            throw new InvalidArgumentException('System role codes cannot be used by custom roles.');
        }

        return new self($id, $organizationId, $code, RoleType::Custom, RoleStatus::Active, self::normalizeName($name), self::normalizeDescription($description), self::unique($permissions), 1);
    }

    /** @param non-empty-list<PermissionCode> $permissions */
    public function update(string $name, ?string $description, array $permissions): void
    {
        $this->requireCustomActive();
        $this->name = self::normalizeName($name);
        $this->description = self::normalizeDescription($description);
        $this->permissions = self::unique($permissions);
        ++$this->version;
    }
    public function archive(): void
    {
        $this->requireCustomActive();
        $this->status = RoleStatus::Archived;
        ++$this->version;
    }
    public function grants(PermissionCode $permission): bool
    {
        return RoleStatus::Active === $this->status && in_array($permission, $this->permissions, true);
    }
    public function id(): RoleId
    {
        return $this->id;
    }
    public function organizationId(): ?OrganizationId
    {
        return $this->organizationId;
    }
    public function code(): RoleCode
    {
        return $this->code;
    }
    public function type(): RoleType
    {
        return $this->type;
    }
    public function status(): RoleStatus
    {
        return $this->status;
    }
    public function name(): string
    {
        return $this->name;
    }
    public function description(): ?string
    {
        return $this->description;
    }
    /** @return non-empty-list<PermissionCode> */ public function permissions(): array
    {
        return $this->permissions;
    }
    public function version(): int
    {
        return $this->version;
    }

    private function requireCustomActive(): void
    {
        if (RoleType::Custom !== $this->type) {
            throw new LogicException('System roles cannot be modified or archived.');
        }
        if (RoleStatus::Active !== $this->status) {
            throw new LogicException('An archived role cannot be modified.');
        }
    }
    private static function normalizeName(string $name): string
    {
        $name = trim($name);
        if ('' === $name || strlen($name) > 120) {
            throw new InvalidArgumentException('Role name must contain between 1 and 120 characters.');
        }
        return $name;
    }
    private static function normalizeDescription(?string $description): ?string
    {
        if (null === $description) {
            return null;
        } $description = trim($description);
        if ('' === $description) {
            return null;
        }
        if (strlen($description) > 500) {
            throw new InvalidArgumentException('Role description cannot exceed 500 characters.');
        }
        return $description;
    }
    /**
     * @param non-empty-list<PermissionCode> $permissions
     * @return non-empty-list<PermissionCode>
     */
    private static function unique(array $permissions): array
    {
        $unique = [];
        foreach ($permissions as $permission) {
            $unique[$permission->value] = $permission;
        }
        return array_values($unique);
    }
}
