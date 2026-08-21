<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Application;

use DateTimeImmutable;
use Zandu\Modules\Organization\Application\Contract\OrganizationOnboardingProvisioner;
use Zandu\Modules\Organization\Domain\CountryCode;
use Zandu\Modules\Organization\Domain\Locale;
use Zandu\Modules\Organization\Domain\Organization;
use Zandu\Modules\Organization\Domain\OrganizationName;
use Zandu\Modules\Organization\Domain\OrganizationRepository;
use Zandu\Modules\Organization\Domain\TimeZone;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Money\Currency;

final readonly class OrganizationOnboardingService implements OrganizationOnboardingProvisioner
{
    public function __construct(private OrganizationRepository $organizations) {}

    public function provision(
        OrganizationId $organizationId,
        ActorId $actorId,
        string $name,
        string $countryCode,
        string $defaultCurrency,
        string $defaultTimeZone,
        string $defaultLocale,
        DateTimeImmutable $occurredAt,
    ): void {
        $this->organizations->save(Organization::create(
            $organizationId,
            OrganizationName::fromString($name),
            CountryCode::fromString($countryCode),
            Currency::fromCode($defaultCurrency),
            TimeZone::fromString($defaultTimeZone),
            Locale::fromString($defaultLocale),
            $actorId,
            $occurredAt,
        ));
    }
}
