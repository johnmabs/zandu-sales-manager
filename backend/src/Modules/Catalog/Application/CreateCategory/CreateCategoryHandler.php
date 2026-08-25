<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Application\CreateCategory;

use Zandu\Modules\Catalog\Domain\Category\Category;
use Zandu\Modules\Catalog\Domain\Category\CategoryName;
use Zandu\Modules\Catalog\Domain\Category\CategoryRepository;
use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Access\ResourceScope;
use Zandu\SharedKernel\Identity\CategoryId;
use Zandu\SharedKernel\Identity\IdGenerator;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class CreateCategoryHandler
{
    public function __construct(
        private CategoryRepository $categories,
        private IdGenerator $idGenerator,
        private Clock $clock,
        private TenantTransaction $transaction,
        private AuthorizationService $authorization,
        private OperationalGuard $operationalGuard,
    ) {}

    public function __invoke(CreateCategory $command): Category
    {
        $organizationId = $command->actorContext->organizationId();

        return $this->transaction->transactional($organizationId, function () use ($command, $organizationId): Category {
            $this->authorization->authorize($command->actorContext, PermissionCode::CategoryCreate, ResourceScope::organization($organizationId));
            $this->operationalGuard->assertTenant($command->actorContext);
            $this->categories->lockHierarchy($organizationId);
            $parent = null !== $command->parentCategoryId
                ? $this->categories->get($organizationId, $command->parentCategoryId)
                : null;
            $category = Category::create(
                CategoryId::generate($this->idGenerator),
                $organizationId,
                CategoryName::fromString($command->name),
                $parent,
                $command->actorContext->actorId(),
                $this->clock->now(),
            );
            $this->categories->save($category);

            return $category;
        });
    }
}
