<?php

declare(strict_types=1);

namespace Zandu\Modules\CashManagement\Presentation\Api;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use InvalidArgumentException;
use Zandu\Modules\CashManagement\Application\CashSessionQueryService;
use Zandu\SharedKernel\Context\CurrentActorProvider;
use Zandu\SharedKernel\Identity\{CashSessionId,StoreId,UuidFactory};
use Zandu\SharedKernel\Tenancy\TenantTransaction;

/** @implements ProviderInterface<CashSessionResource> */
final readonly class CashSessionProvider implements ProviderInterface
{
    public function __construct(private CurrentActorProvider $actors, private UuidFactory $uuids, private TenantTransaction $transaction, private CashSessionQueryService $sessions) {}public function provide(Operation $o, array $u = [], array $c = []): CashSessionResource
    {
        $a = $this->actors->resolve();
        $s = StoreId::fromString((string) ($u['storeId'] ?? throw new InvalidArgumentException('Store identifier is required.')), $this->uuids);
        $id = CashSessionId::fromString((string) ($u['id'] ?? throw new InvalidArgumentException('Session identifier is required.')), $this->uuids);
        return $this->transaction->transactional($a->organizationId(), function () use ($a, $s, $id): CashSessionResource {
            $v = $this->sessions->get($a, $s, $id);
            return new CashSessionResource(...array_values($v));
        });
    }
}
