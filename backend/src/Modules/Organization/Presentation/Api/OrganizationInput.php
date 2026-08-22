<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Presentation\Api;

use Symfony\Component\Validator\Constraints as Assert;

final class OrganizationInput
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 160)]
    public string $name = '';

    #[Assert\NotBlank]
    #[Assert\Regex('/^[A-Za-z]{2}$/')]
    public string $countryCode = '';

    #[Assert\NotBlank]
    #[Assert\Regex('/^[A-Za-z]{3}$/')]
    public string $defaultCurrency = '';

    #[Assert\NotBlank]
    #[Assert\Length(max: 64)]
    public string $defaultTimeZone = '';

    #[Assert\NotBlank]
    #[Assert\Length(max: 16)]
    public string $defaultLocale = '';
}
