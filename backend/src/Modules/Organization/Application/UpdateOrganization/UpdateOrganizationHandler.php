<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Application\UpdateOrganization;

use Zandu\Modules\Organization\Application\TenantOrganizationLoader;
use Zandu\Modules\Organization\Domain\CountryCode;
use Zandu\Modules\Organization\Domain\Locale;
use Zandu\Modules\Organization\Domain\Organization;
use Zandu\Modules\Organization\Domain\OrganizationName;
use Zandu\Modules\Organization\Domain\OrganizationRepository;
use Zandu\Modules\Organization\Domain\TimeZone;
use Zandu\SharedKernel\Money\Currency;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class UpdateOrganizationHandler
{
    public function __construct(
        private TenantOrganizationLoader $loader,
        private OrganizationRepository $organizations,
        private Clock $clock,
        private TenantTransaction $transaction,
    ) {}

    public function __invoke(UpdateOrganization $command): Organization
    {
        return $this->transaction->transactional(
            $command->actorContext->organizationId(),
            function () use ($command): Organization {
                $organization = $this->loader->get($command->organizationId, $command->actorContext);
                $organization->updateProfile(
                    OrganizationName::fromString($command->name),
                    CountryCode::fromString($command->countryCode),
                    Currency::fromCode($command->defaultCurrency),
                    TimeZone::fromString($command->defaultTimeZone),
                    Locale::fromString($command->defaultLocale),
                    $command->actorContext->actorId(),
                    $this->clock->now(),
                );
                $this->organizations->save($organization);

                return $organization;
            },
        );
    }
}
