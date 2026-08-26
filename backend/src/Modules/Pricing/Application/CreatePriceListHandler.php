<?php

declare(strict_types=1);

namespace Zandu\Modules\Pricing\Application;

use LogicException;
use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\Modules\Pricing\Domain\PriceList\PriceList;
use Zandu\Modules\Pricing\Domain\PriceList\PriceListCode;
use Zandu\Modules\Pricing\Domain\PriceList\PriceListName;
use Zandu\Modules\Pricing\Domain\PriceList\PriceListPriority;
use Zandu\Modules\Pricing\Domain\PriceList\PriceListRepository;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Access\ResourceScope;
use Zandu\SharedKernel\Identity\IdGenerator;
use Zandu\SharedKernel\Identity\PriceListId;
use Zandu\SharedKernel\Money\Currency;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class CreatePriceListHandler
{
    public function __construct(
        private PriceListRepository $lists,
        private IdGenerator $ids,
        private Clock $clock,
        private TenantTransaction $transaction,
        private AuthorizationService $authorization,
        private OperationalGuard $guard,
    ) {}

    public function __invoke(CreatePriceList $command): PriceList
    {
        $organizationId = $command->actorContext->organizationId();

        return $this->transaction->transactional($organizationId, function () use ($command, $organizationId): PriceList {
            $this->authorization->authorize($command->actorContext, PermissionCode::PriceListCreate, ResourceScope::organization($organizationId));
            $this->guard->assertTenant($command->actorContext);
            $code = PriceListCode::fromString($command->code);
            if (null !== $this->lists->findByCode($organizationId, $code)) {
                throw new LogicException('Price list code already exists.');
            }

            $priceList = PriceList::createDraft(PriceListId::generate($this->ids), $organizationId, $code, PriceListName::fromString($command->name), Currency::fromCode($command->currency), $command->validFrom, $command->validTo, PriceListPriority::fromInt($command->priority), $command->actorContext->actorId(), $this->clock->now());
            $this->lists->save($priceList);

            return $priceList;
        });
    }
}
