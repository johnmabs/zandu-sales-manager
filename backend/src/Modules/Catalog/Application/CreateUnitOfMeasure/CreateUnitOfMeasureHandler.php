<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Application\CreateUnitOfMeasure;

use Zandu\Modules\Catalog\Application\UnitOfMeasureAttributes;
use Zandu\Modules\Catalog\Domain\UnitOfMeasure;
use Zandu\Modules\Catalog\Domain\UnitOfMeasureCode;
use Zandu\Modules\Catalog\Domain\UnitOfMeasureCodeAlreadyExists;
use Zandu\Modules\Catalog\Domain\UnitOfMeasureRepository;
use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Access\ResourceScope;
use Zandu\SharedKernel\Identity\IdGenerator;
use Zandu\SharedKernel\Identity\UnitOfMeasureId;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class CreateUnitOfMeasureHandler
{
    public function __construct(
        private UnitOfMeasureRepository $units,
        private IdGenerator $idGenerator,
        private Clock $clock,
        private TenantTransaction $transaction,
        private AuthorizationService $authorization,
        private OperationalGuard $operationalGuard,
    ) {}

    public function __invoke(CreateUnitOfMeasure $command): UnitOfMeasure
    {
        $organizationId = $command->actorContext->organizationId();

        return $this->transaction->transactional($organizationId, function () use ($command, $organizationId): UnitOfMeasure {
            $this->authorization->authorize($command->actorContext, PermissionCode::UnitOfMeasureCreate, ResourceScope::organization($organizationId));
            $this->operationalGuard->assertTenant($command->actorContext);
            $code = UnitOfMeasureCode::fromString($command->code);
            if ($this->units->codeExists($organizationId, $code)) {
                throw UnitOfMeasureCodeAlreadyExists::withCode($code);
            }
            $attributes = UnitOfMeasureAttributes::fromPrimitives(
                $command->name,
                $command->dimension,
                $command->precision,
                $command->roundingMode,
            );
            $unit = UnitOfMeasure::create(
                UnitOfMeasureId::generate($this->idGenerator),
                $organizationId,
                $code,
                $attributes->name,
                $attributes->dimension,
                $attributes->precision,
                $attributes->roundingMode,
                $command->actorContext->actorId(),
                $this->clock->now(),
            );
            $this->units->save($unit);

            return $unit;
        });
    }
}
