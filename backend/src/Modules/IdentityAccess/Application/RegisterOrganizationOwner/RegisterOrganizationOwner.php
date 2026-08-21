<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Application\RegisterOrganizationOwner;

use SensitiveParameter;
use Zandu\SharedKernel\Messaging\CorrelationId;

final readonly class RegisterOrganizationOwner
{
    public function __construct(
        public string $email,
        #[SensitiveParameter]
        public string $password,
        public string $organizationName,
        public string $countryCode,
        public string $defaultCurrency,
        public string $defaultTimeZone,
        public string $defaultLocale,
        public CorrelationId $correlationId,
    ) {}
}
