<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Application;

use Zandu\Modules\Catalog\Domain\ProductPackaging\ProductPackaging;
use Zandu\Modules\Catalog\Domain\ProductPackaging\ProductPackagingRepository;
use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Access\ResourceScope;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class ArchiveProductPackagingHandler
{
    public function __construct(
        private ProductPackagingRepository $packagings,
        private Clock $clock,
        private TenantTransaction $transaction,
        private AuthorizationService $authorization,
        private OperationalGuard $guard,
    ) {}

    public function __invoke(ArchiveProductPackaging $command): ProductPackaging
    {
        $organizationId = $command->actorContext->organizationId();

        return $this->transaction->transactional($organizationId, function () use ($command, $organizationId): ProductPackaging {
            $this->authorization->authorize($command->actorContext, PermissionCode::ProductArchive, ResourceScope::organization($organizationId));
            $this->guard->assertTenant($command->actorContext);
            $packaging = $this->packagings->get($organizationId, $command->packagingId);
            $packaging->archive($command->actorContext->actorId(), $this->clock->now());
            $this->packagings->save($packaging);

            return $packaging;
        });
    }
}
