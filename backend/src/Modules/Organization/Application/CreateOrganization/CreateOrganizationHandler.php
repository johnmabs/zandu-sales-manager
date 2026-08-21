<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Application\CreateOrganization;

use Zandu\Modules\Organization\Application\Contract\InitialOrganizationOwnerProvisioner;
use Zandu\Modules\Organization\Domain\CountryCode;
use Zandu\Modules\Organization\Domain\Locale;
use Zandu\Modules\Organization\Domain\Organization;
use Zandu\Modules\Organization\Domain\OrganizationName;
use Zandu\Modules\Organization\Domain\OrganizationRepository;
use Zandu\Modules\Organization\Domain\TimeZone;
use Zandu\SharedKernel\Identity\IdGenerator;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Money\Currency;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class CreateOrganizationHandler
{
    public function __construct(
        private OrganizationRepository $organizations,
        private IdGenerator $idGenerator,
        private Clock $clock,
        private TenantTransaction $transaction,
        private InitialOrganizationOwnerProvisioner $initialOwner,
    ) {}

    public function __invoke(CreateOrganization $command): Organization
    {
        $organizationId = OrganizationId::generate($this->idGenerator);

        return $this->transaction->transactional(
            $organizationId,
            function () use ($command, $organizationId): Organization {
                $now = $this->clock->now();
                $organization = Organization::create(
                    $organizationId,
                    OrganizationName::fromString($command->name),
                    CountryCode::fromString($command->countryCode),
                    Currency::fromCode($command->defaultCurrency),
                    TimeZone::fromString($command->defaultTimeZone),
                    Locale::fromString($command->defaultLocale),
                    $command->actorContext->actorId(),
                    $now,
                );
                $this->organizations->save($organization);
                $this->initialOwner->provision($organizationId, $command->actorContext, $now);

                return $organization;
            },
        );
    }
}
