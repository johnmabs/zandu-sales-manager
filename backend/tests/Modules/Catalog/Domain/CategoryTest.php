<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Catalog\Domain;

use DateTimeImmutable;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\Catalog\Domain\Category\Category;
use Zandu\Modules\Catalog\Domain\Category\CategoryName;
use Zandu\Modules\Catalog\Domain\Category\CategoryStatus;
use Zandu\Modules\Catalog\Domain\Category\Event\CategoryActivated;
use Zandu\Modules\Catalog\Domain\Category\Event\CategoryArchived;
use Zandu\Modules\Catalog\Domain\Category\Event\CategoryCreated;
use Zandu\Modules\Catalog\Domain\Category\Event\CategoryDeactivated;
use Zandu\Modules\Catalog\Domain\Category\Event\CategoryMoved;
use Zandu\Modules\Catalog\Domain\Category\Event\CategoryUpdated;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\CategoryId;
use Zandu\SharedKernel\Identity\OrganizationId;

final class CategoryTest extends TestCase
{
    private const CATEGORY_ID = '0198d281-147c-72d5-b75a-a936797ff9c8';
    private const PARENT_ID = '0198d282-147c-72d5-b75a-a936797ff9c8';
    private const GRANDPARENT_ID = '0198d283-147c-72d5-b75a-a936797ff9c8';
    private const ORGANIZATION_ID = '0198d1b1-b2a4-7b6e-8e0e-608484906502';
    private const OTHER_ORGANIZATION_ID = '0198d1b1-b2a4-7b6e-8e0e-608484906503';
    private const ACTOR_ID = '0198c728-8f2d-7f43-92d8-3f0c75b80186';

    public function testNameIsRequiredAndNormalized(): void
    {
        self::assertSame('Boissons chaudes', CategoryName::fromString(' Boissons   chaudes ')->value());

        $this->expectException(InvalidArgumentException::class);
        CategoryName::fromString('  ');
    }

    public function testCreationIsActiveTenantOwnedAndAudited(): void
    {
        $category = $this->category();
        $events = $category->releaseEvents();

        self::assertSame(self::ORGANIZATION_ID, $category->organizationId()->toString());
        self::assertSame(CategoryStatus::Active, $category->status());
        self::assertNull($category->parentCategoryId());
        self::assertNull($category->updatedAt());
        self::assertNull($category->updatedBy());
        self::assertSame('UTC', $category->createdAt()->getTimezone()->getName());
        self::assertSame(1, $category->version());
        self::assertInstanceOf(CategoryCreated::class, $events[0]);
    }

    public function testParentMustBelongToTheSameTenant(): void
    {
        $parent = $this->category(self::PARENT_ID, self::OTHER_ORGANIZATION_ID, 'Autre tenant');

        $this->expectException(LogicException::class);
        Category::create(
            $this->categoryId(self::CATEGORY_ID),
            $this->organizationId(),
            CategoryName::fromString('Enfant'),
            $parent,
            $this->actorId(),
            new DateTimeImmutable('2026-08-25T18:00:00Z'),
        );
    }

    public function testCategoryCannotBeItsOwnParent(): void
    {
        $category = $this->category();

        $this->expectException(LogicException::class);
        $category->moveTo($category, [], $this->actorId(), new DateTimeImmutable('2026-08-25T19:00:00Z'));
    }

    public function testMoveRejectsAParentWhoseAncestorsContainTheCategory(): void
    {
        $category = $this->category();
        $parent = $this->category(self::PARENT_ID, self::ORGANIZATION_ID, 'Enfant actuel');

        $this->expectException(LogicException::class);
        $category->moveTo(
            $parent,
            [$this->categoryId(self::GRANDPARENT_ID), $category->id()],
            $this->actorId(),
            new DateTimeImmutable('2026-08-25T19:00:00Z'),
        );
    }

    public function testUpdateAndMoveRecordAuditAndEvents(): void
    {
        $category = $this->category();
        $parent = $this->category(self::PARENT_ID, self::ORGANIZATION_ID, 'Alimentation');
        $category->releaseEvents();
        $occurredAt = new DateTimeImmutable('2026-08-25T20:00:00+01:00');

        $category->update(CategoryName::fromString('Boissons froides'), $this->actorId(), $occurredAt);
        self::assertSame('Boissons froides', $category->name()->value());
        self::assertInstanceOf(CategoryUpdated::class, $category->releaseEvents()[0]);

        $category->moveTo($parent, [], $this->actorId(), $occurredAt);
        self::assertTrue($category->parentCategoryId()?->equals($parent->id()));
        self::assertSame('UTC', $category->updatedAt()?->getTimezone()->getName());
        self::assertSame(self::ACTOR_ID, $category->updatedBy()?->toString());
        self::assertSame(3, $category->version());
        self::assertInstanceOf(CategoryMoved::class, $category->releaseEvents()[0]);
    }

    public function testLifecycleIsExplicitAndArchivedCategoryRemainsResolvable(): void
    {
        $category = $this->category();
        $category->releaseEvents();
        $occurredAt = new DateTimeImmutable('2026-08-25T21:00:00Z');

        $category->deactivate($this->actorId(), $occurredAt);
        self::assertSame(CategoryStatus::Inactive, $category->status());
        self::assertInstanceOf(CategoryDeactivated::class, $category->releaseEvents()[0]);

        $category->activate($this->actorId(), $occurredAt);
        self::assertSame(CategoryStatus::Active, $category->status());
        self::assertInstanceOf(CategoryActivated::class, $category->releaseEvents()[0]);

        $category->archive($this->actorId(), $occurredAt);
        self::assertSame(CategoryStatus::Archived, $category->status());
        self::assertSame('Boissons', $category->name()->value());
        self::assertInstanceOf(CategoryArchived::class, $category->releaseEvents()[0]);

        $this->expectException(LogicException::class);
        $category->ensureSelectable();
    }

    public function testArchivedCategoryCannotReturnToAnOperationalState(): void
    {
        $category = $this->category();
        $category->archive($this->actorId(), new DateTimeImmutable('2026-08-25T21:00:00Z'));

        $this->expectException(LogicException::class);
        $category->activate($this->actorId(), new DateTimeImmutable('2026-08-25T22:00:00Z'));
    }

    public function testReconstitutionRejectsSelfParent(): void
    {
        $this->expectException(LogicException::class);
        Category::reconstitute(
            $this->categoryId(self::CATEGORY_ID),
            $this->organizationId(),
            CategoryName::fromString('Cycle'),
            $this->categoryId(self::CATEGORY_ID),
            CategoryStatus::Active,
            new DateTimeImmutable('2026-08-25T18:00:00Z'),
            $this->actorId(),
            null,
            null,
            1,
        );
    }

    public function testReconstitutionRejectsInvalidVersion(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Category::reconstitute(
            $this->categoryId(self::CATEGORY_ID),
            $this->organizationId(),
            CategoryName::fromString('Version invalide'),
            null,
            CategoryStatus::Active,
            new DateTimeImmutable('2026-08-25T18:00:00Z'),
            $this->actorId(),
            null,
            null,
            0,
        );
    }

    private function category(
        string $id = self::CATEGORY_ID,
        string $organizationId = self::ORGANIZATION_ID,
        string $name = 'Boissons',
    ): Category {
        return Category::create(
            $this->categoryId($id),
            $this->organizationId($organizationId),
            CategoryName::fromString($name),
            null,
            $this->actorId(),
            new DateTimeImmutable('2026-08-25T18:00:00+01:00'),
        );
    }

    private function categoryId(string $id): CategoryId
    {
        return CategoryId::fromString($id, new SymfonyUuidFactory());
    }

    private function organizationId(string $id = self::ORGANIZATION_ID): OrganizationId
    {
        return OrganizationId::fromString($id, new SymfonyUuidFactory());
    }

    private function actorId(): ActorId
    {
        return ActorId::fromString(self::ACTOR_ID, new SymfonyUuidFactory());
    }
}
