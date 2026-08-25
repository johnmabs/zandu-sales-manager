<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Domain\Category;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use LogicException;
use Zandu\Modules\Catalog\Domain\Category\Event\CategoryActivated;
use Zandu\Modules\Catalog\Domain\Category\Event\CategoryArchived;
use Zandu\Modules\Catalog\Domain\Category\Event\CategoryCreated;
use Zandu\Modules\Catalog\Domain\Category\Event\CategoryDeactivated;
use Zandu\Modules\Catalog\Domain\Category\Event\CategoryEvent;
use Zandu\Modules\Catalog\Domain\Category\Event\CategoryMoved;
use Zandu\Modules\Catalog\Domain\Category\Event\CategoryUpdated;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\CategoryId;
use Zandu\SharedKernel\Identity\OrganizationId;

final class Category
{
    /** @var list<CategoryEvent> */
    private array $recordedEvents = [];

    private function __construct(
        private readonly CategoryId $id,
        private readonly OrganizationId $organizationId,
        private CategoryName $name,
        private ?CategoryId $parentCategoryId,
        private CategoryStatus $status,
        private readonly DateTimeImmutable $createdAt,
        private readonly ActorId $createdBy,
        private ?DateTimeImmutable $updatedAt,
        private ?ActorId $updatedBy,
        private int $version,
    ) {
        if ($version < 1) {
            throw new InvalidArgumentException('Category version must be positive.');
        }
        if ($parentCategoryId?->equals($id)) {
            throw new LogicException('A category cannot be its own parent.');
        }
    }

    public static function create(
        CategoryId $id,
        OrganizationId $organizationId,
        CategoryName $name,
        ?self $parent,
        ActorId $actorId,
        DateTimeImmutable $occurredAt,
    ): self {
        self::assertValidParent($id, $organizationId, $parent, []);
        $occurredAt = self::utc($occurredAt);
        $category = new self(
            $id,
            $organizationId,
            $name,
            $parent?->id(),
            CategoryStatus::Active,
            $occurredAt,
            $actorId,
            null,
            null,
            1,
        );
        $category->recordedEvents[] = new CategoryCreated($organizationId, $id, $actorId, $occurredAt);

        return $category;
    }

    public static function reconstitute(
        CategoryId $id,
        OrganizationId $organizationId,
        CategoryName $name,
        ?CategoryId $parentCategoryId,
        CategoryStatus $status,
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
            $parentCategoryId,
            $status,
            self::utc($createdAt),
            $createdBy,
            null !== $updatedAt ? self::utc($updatedAt) : null,
            $updatedBy,
            $version,
        );
    }

    public function update(CategoryName $name, ActorId $actorId, DateTimeImmutable $occurredAt): void
    {
        $this->requireNotArchived('An archived category cannot be updated.');
        $this->name = $name;
        $occurredAt = $this->changedBy($actorId, $occurredAt);
        $this->recordedEvents[] = new CategoryUpdated($this->organizationId, $this->id, $actorId, $occurredAt);
    }

    /** @param list<CategoryId> $parentAncestorIds */
    public function moveTo(
        ?self $parent,
        array $parentAncestorIds,
        ActorId $actorId,
        DateTimeImmutable $occurredAt,
    ): void {
        $this->requireNotArchived('An archived category cannot be moved.');
        self::assertValidParent($this->id, $this->organizationId, $parent, $parentAncestorIds);
        $this->parentCategoryId = $parent?->id();
        $occurredAt = $this->changedBy($actorId, $occurredAt);
        $this->recordedEvents[] = new CategoryMoved($this->organizationId, $this->id, $actorId, $occurredAt);
    }

    public function deactivate(ActorId $actorId, DateTimeImmutable $occurredAt): void
    {
        $this->requireStatus(CategoryStatus::Active, 'Only an active category can be deactivated.');
        $this->status = CategoryStatus::Inactive;
        $occurredAt = $this->changedBy($actorId, $occurredAt);
        $this->recordedEvents[] = new CategoryDeactivated($this->organizationId, $this->id, $actorId, $occurredAt);
    }

    public function activate(ActorId $actorId, DateTimeImmutable $occurredAt): void
    {
        $this->requireStatus(CategoryStatus::Inactive, 'Only an inactive category can be activated.');
        $this->status = CategoryStatus::Active;
        $occurredAt = $this->changedBy($actorId, $occurredAt);
        $this->recordedEvents[] = new CategoryActivated($this->organizationId, $this->id, $actorId, $occurredAt);
    }

    public function archive(ActorId $actorId, DateTimeImmutable $occurredAt): void
    {
        $this->requireNotArchived('Category is already archived.');
        $this->status = CategoryStatus::Archived;
        $occurredAt = $this->changedBy($actorId, $occurredAt);
        $this->recordedEvents[] = new CategoryArchived($this->organizationId, $this->id, $actorId, $occurredAt);
    }

    public function ensureSelectable(): void
    {
        $this->requireStatus(CategoryStatus::Active, 'Only an active category can be selected.');
    }

    /** @return list<CategoryEvent> */
    public function releaseEvents(): array
    {
        $events = $this->recordedEvents;
        $this->recordedEvents = [];

        return $events;
    }

    public function id(): CategoryId
    {
        return $this->id;
    }

    public function organizationId(): OrganizationId
    {
        return $this->organizationId;
    }

    public function name(): CategoryName
    {
        return $this->name;
    }

    public function parentCategoryId(): ?CategoryId
    {
        return $this->parentCategoryId;
    }

    public function status(): CategoryStatus
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

    /** @param list<CategoryId> $parentAncestorIds */
    private static function assertValidParent(
        CategoryId $id,
        OrganizationId $organizationId,
        ?self $parent,
        array $parentAncestorIds,
    ): void {
        if (null === $parent) {
            if ([] !== $parentAncestorIds) {
                throw new LogicException('A root category cannot have parent ancestors.');
            }

            return;
        }
        if (!$parent->organizationId()->equals($organizationId)) {
            throw new LogicException('A parent category must belong to the same organization.');
        }
        if ($parent->id()->equals($id)) {
            throw new LogicException('A category cannot be its own parent.');
        }
        foreach ($parentAncestorIds as $ancestorId) {
            if ($ancestorId->equals($id)) {
                throw new LogicException('A category hierarchy cannot contain a cycle.');
            }
        }
    }

    private function changedBy(ActorId $actorId, DateTimeImmutable $occurredAt): DateTimeImmutable
    {
        $occurredAt = self::utc($occurredAt);
        $this->updatedBy = $actorId;
        $this->updatedAt = $occurredAt;
        ++$this->version;

        return $occurredAt;
    }

    private function requireStatus(CategoryStatus $status, string $message): void
    {
        if ($this->status !== $status) {
            throw new LogicException($message);
        }
    }

    private function requireNotArchived(string $message): void
    {
        if (CategoryStatus::Archived === $this->status) {
            throw new LogicException($message);
        }
    }

    private static function utc(DateTimeImmutable $dateTime): DateTimeImmutable
    {
        return $dateTime->setTimezone(new DateTimeZone('UTC'));
    }
}
