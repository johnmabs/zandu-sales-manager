<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Domain\Access;

use InvalidArgumentException;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\StoreId;

final readonly class AccessScope
{
    /** @param list<StoreId> $storeIds */
    private function __construct(
        private AccessScopeType $type,
        private OrganizationId $organizationId,
        private array $storeIds,
    ) {}

    public static function organization(OrganizationId $organizationId): self
    {
        return new self(AccessScopeType::Organization, $organizationId, []);
    }

    /** @param non-empty-list<ScopedStore> $stores */
    public static function selectedStores(OrganizationId $organizationId, array $stores): self
    {
        $storeIds = [];
        foreach ($stores as $store) {
            if (!$store->belongsTo($organizationId)) {
                throw new InvalidArgumentException('All selected stores must belong to the scoped organization.');
            }

            $storeIds[$store->storeId()->toString()] = $store->storeId();
        }

        return new self(AccessScopeType::SelectedStores, $organizationId, array_values($storeIds));
    }

    public function type(): AccessScopeType
    {
        return $this->type;
    }

    public function organizationId(): OrganizationId
    {
        return $this->organizationId;
    }

    /** @return list<StoreId> */
    public function storeIds(): array
    {
        return $this->storeIds;
    }

    public function includesStore(StoreId $storeId): bool
    {
        if (AccessScopeType::Organization === $this->type) {
            return true;
        }

        foreach ($this->storeIds as $selectedStoreId) {
            if ($selectedStoreId->equals($storeId)) {
                return true;
            }
        }

        return false;
    }
}
