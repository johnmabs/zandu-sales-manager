<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Application\RequestStoreClosure;

use Zandu\Modules\Organization\Application\Contract\StoreClosureBlockerProvider;
use Zandu\Modules\Organization\Application\TenantStoreLoader;
use Zandu\Modules\Organization\Domain\Store\StoreRepository;
use Zandu\Modules\Organization\Domain\StoreClosure\StoreClosure;
use Zandu\Modules\Organization\Domain\StoreClosure\StoreClosureRepository;
use Zandu\SharedKernel\Identity\IdGenerator;
use Zandu\SharedKernel\Identity\StoreClosureId;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class RequestStoreClosureHandler
{
    public function __construct(
        private TenantStoreLoader $loader,
        private StoreRepository $stores,
        private StoreClosureRepository $closures,
        private StoreClosureBlockerProvider $blockerProvider,
        private IdGenerator $idGenerator,
        private Clock $clock,
        private TenantTransaction $transaction,
    ) {}

    public function __invoke(RequestStoreClosure $command): StoreClosure
    {
        $organizationId = $command->actorContext->organizationId();

        return $this->transaction->transactional($organizationId, function () use ($command, $organizationId): StoreClosure {
            $store = $this->loader->get($command->storeId, $command->actorContext);
            $occurredAt = $this->clock->now();
            $store->requestClosure($command->actorContext->actorId(), $occurredAt);
            $closure = StoreClosure::request(
                StoreClosureId::generate($this->idGenerator),
                $organizationId,
                $store->id(),
                $command->reason,
                $command->actorContext->actorId(),
                $occurredAt,
            );
            $closure->evaluate($this->blockerProvider->blockers($organizationId, $store->id()));
            $this->stores->save($store);
            $this->closures->save($closure);

            return $closure;
        });
    }
}
