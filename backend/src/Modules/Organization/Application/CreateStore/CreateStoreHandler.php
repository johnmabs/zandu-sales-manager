<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Application\CreateStore;

use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\IdentityAccess\Application\Contract\ResourceScope;
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\Modules\Organization\Domain\Locale;
use Zandu\Modules\Organization\Domain\OrganizationRepository;
use Zandu\Modules\Organization\Domain\Store\Store;
use Zandu\Modules\Organization\Domain\Store\StoreAddress;
use Zandu\Modules\Organization\Domain\Store\StoreCode;
use Zandu\Modules\Organization\Domain\Store\StoreCodeAlreadyExists;
use Zandu\Modules\Organization\Domain\Store\StoreName;
use Zandu\Modules\Organization\Domain\Store\StoreRepository;
use Zandu\Modules\Organization\Domain\TimeZone;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Identity\IdGenerator;
use Zandu\SharedKernel\Identity\StoreId;
use Zandu\SharedKernel\Money\Currency;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class CreateStoreHandler
{
    public function __construct(
        private OrganizationRepository $organizations,
        private StoreRepository $stores,
        private IdGenerator $idGenerator,
        private Clock $clock,
        private TenantTransaction $transaction,
        private AuthorizationService $authorization,
        private OperationalGuard $operationalGuard,
    ) {}

    public function __invoke(CreateStore $command): Store
    {
        $organizationId = $command->actorContext->organizationId();

        return $this->transaction->transactional($organizationId, function () use ($command, $organizationId): Store {
            $organization = $this->organizations->get($organizationId);
            $this->authorization->authorize($command->actorContext, PermissionCode::StoreCreate, ResourceScope::organization($organizationId));
            $this->operationalGuard->assertTenant($command->actorContext);

            $code = StoreCode::fromString($command->code);
            if ($this->stores->codeExists($organizationId, $code)) {
                throw StoreCodeAlreadyExists::withCode($code);
            }

            $store = Store::create(
                StoreId::generate($this->idGenerator),
                $organizationId,
                $code,
                StoreName::fromString($command->name),
                null !== $command->address ? StoreAddress::fromString($command->address) : null,
                TimeZone::fromString($command->timeZone),
                Currency::fromCode($command->currency),
                Locale::fromString($command->locale),
                $organization->defaultCurrency(),
                $command->actorContext->actorId(),
                $this->clock->now(),
            );
            $this->stores->save($store);

            return $store;
        });
    }
}
