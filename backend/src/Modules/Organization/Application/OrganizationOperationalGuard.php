<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Application;

use LogicException;
use Zandu\Modules\Organization\Application\Contract\OperationalMode;
use Zandu\Modules\Organization\Domain\Organization;
use Zandu\Modules\Organization\Domain\OrganizationStatus;

final readonly class OrganizationOperationalGuard
{
    public function assertAllows(Organization $organization, OperationalMode $mode = OperationalMode::Standard): void
    {
        $allowed = match ($organization->status()) {
            OrganizationStatus::Active => true,
            OrganizationStatus::Suspended => OperationalMode::Standard !== $mode,
            OrganizationStatus::ClosurePending => OperationalMode::Termination === $mode,
            OrganizationStatus::Closed => false,
        };

        if (!$allowed) {
            throw new LogicException(sprintf(
                'Organization status "%s" does not allow a %s operation.',
                $organization->status()->value,
                strtolower($mode->name),
            ));
        }
    }
}
