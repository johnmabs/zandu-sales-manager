<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Infrastructure\Persistence\Orm;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Zandu\Modules\Catalog\Domain\Category\Category;

#[ORM\Entity]
#[ORM\Table(name: 'categories', schema: 'catalog')]
#[ORM\UniqueConstraint(name: 'category_tenant_id_unique', columns: ['organization_id', 'id'])]
final class CategoryRecord
{
    private function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'guid')]
        private string $id,
        #[ORM\Column(type: 'guid')]
        private string $organizationId,
        #[ORM\Column(length: 160)]
        private string $name,
        #[ORM\Column(type: 'guid', nullable: true)]
        private ?string $parentCategoryId,
        #[ORM\Column(length: 16)]
        private string $status,
        #[ORM\Column(type: 'datetimetz_immutable')]
        private DateTimeImmutable $createdAt,
        #[ORM\Column(type: 'guid')]
        private string $createdBy,
        #[ORM\Column(type: 'datetimetz_immutable', nullable: true)]
        private ?DateTimeImmutable $updatedAt,
        #[ORM\Column(type: 'guid', nullable: true)]
        private ?string $updatedBy,
        #[ORM\Version]
        #[ORM\Column(type: 'integer')]
        private int $version,
    ) {}

    public static function fromAggregate(Category $category): self
    {
        return new self(
            $category->id()->toString(),
            $category->organizationId()->toString(),
            $category->name()->value(),
            $category->parentCategoryId()?->toString(),
            $category->status()->value,
            $category->createdAt(),
            $category->createdBy()->toString(),
            $category->updatedAt(),
            $category->updatedBy()?->toString(),
            $category->version(),
        );
    }

    public function synchronize(Category $category): void
    {
        $current = self::fromAggregate($category);
        $this->name = $current->name;
        $this->parentCategoryId = $current->parentCategoryId;
        $this->status = $current->status;
        $this->updatedAt = $current->updatedAt;
        $this->updatedBy = $current->updatedBy;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function organizationId(): string
    {
        return $this->organizationId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function parentCategoryId(): ?string
    {
        return $this->parentCategoryId;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function createdBy(): string
    {
        return $this->createdBy;
    }

    public function updatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function updatedBy(): ?string
    {
        return $this->updatedBy;
    }

    public function version(): int
    {
        return $this->version;
    }
}
