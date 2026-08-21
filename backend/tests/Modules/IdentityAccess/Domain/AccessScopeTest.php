<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\IdentityAccess\Domain;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\IdentityAccess\Domain\Access\AccessScope;
use Zandu\Modules\IdentityAccess\Domain\Access\AccessScopeType;
use Zandu\Modules\IdentityAccess\Domain\Access\ScopedStore;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\StoreId;

final class AccessScopeTest extends TestCase
{
    public function testOrganizationScopeIncludesEveryStore(): void
    {
        $scope = AccessScope::organization($this->organizationId('0198d1b1-b2a4-7b6e-8e0e-608484906502'));

        self::assertSame(AccessScopeType::Organization, $scope->type());
        self::assertTrue($scope->includesStore($this->storeId('0198d601-147c-72d5-b75a-a936797ff9c8')));
        self::assertSame([], $scope->storeIds());
    }

    public function testSelectedStoreScopeIsDeduplicatedAndRestricted(): void
    {
        $organizationId = $this->organizationId('0198d1b1-b2a4-7b6e-8e0e-608484906502');
        $selectedStoreId = $this->storeId('0198d601-147c-72d5-b75a-a936797ff9c8');
        $scope = AccessScope::selectedStores($organizationId, [
            new ScopedStore($selectedStoreId, $organizationId),
            new ScopedStore($selectedStoreId, $organizationId),
        ]);

        self::assertSame(AccessScopeType::SelectedStores, $scope->type());
        self::assertCount(1, $scope->storeIds());
        self::assertTrue($scope->includesStore($selectedStoreId));
        self::assertFalse($scope->includesStore($this->storeId('0198d601-147c-72d5-b75a-a936797ff9c9')));
    }

    public function testSelectedStoresCannotCrossOrganizationBoundary(): void
    {
        $this->expectException(InvalidArgumentException::class);

        AccessScope::selectedStores(
            $this->organizationId('0198d1b1-b2a4-7b6e-8e0e-608484906502'),
            [new ScopedStore(
                $this->storeId('0198d601-147c-72d5-b75a-a936797ff9c8'),
                $this->organizationId('0198d1b1-b2a4-7b6e-8e0e-608484906503'),
            )],
        );
    }

    private function organizationId(string $value): OrganizationId
    {
        return OrganizationId::fromString($value, new SymfonyUuidFactory());
    }

    private function storeId(string $value): StoreId
    {
        return StoreId::fromString($value, new SymfonyUuidFactory());
    }
}
