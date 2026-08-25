<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Application\UpdateUnitOfMeasure;

use Zandu\Modules\Catalog\Application\TenantUnitOfMeasureLoader;
use Zandu\Modules\Catalog\Application\UnitOfMeasureAttributes;
use Zandu\Modules\Catalog\Domain\UnitOfMeasure;
use Zandu\Modules\Catalog\Domain\UnitOfMeasureRepository;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class UpdateUnitOfMeasureHandler
{
    public function __construct(
        private TenantUnitOfMeasureLoader $loader,
        private UnitOfMeasureRepository $units,
        private Clock $clock,
        private TenantTransaction $transaction,
    ) {}

    public function __invoke(UpdateUnitOfMeasure $command): UnitOfMeasure
    {
        return $this->transaction->transactional($command->actorContext->organizationId(), function () use ($command): UnitOfMeasure {
            $unit = $this->loader->get($command->unitId, $command->actorContext);
            $attributes = UnitOfMeasureAttributes::fromPrimitives(
                $command->name,
                $command->dimension,
                $command->precision,
                $command->roundingMode,
            );
            $unit->update(
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
