<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Organization\Application;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\Organization\Application\OrganizationViewFactory;
use Zandu\Modules\Organization\Domain\CountryCode;
use Zandu\Modules\Organization\Domain\Locale;
use Zandu\Modules\Organization\Domain\Organization;
use Zandu\Modules\Organization\Domain\OrganizationName;
use Zandu\Modules\Organization\Domain\TimeZone;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Money\Currency;

final class OrganizationViewFactoryTest extends TestCase
{
    public function testItExposesAnApplicationViewWithoutLeakingTheAggregate(): void
    {
        $uuids = new SymfonyUuidFactory();
        $organization = Organization::create(
            OrganizationId::fromString('0198c728-a648-75b7-b7d7-c69d0bf84390', $uuids),
            OrganizationName::fromString('Zandu Congo'),
            CountryCode::fromString('CG'),
            Currency::fromCode('XAF'),
            TimeZone::fromString('Africa/Brazzaville'),
            Locale::fromString('fr_CG'),
            ActorId::fromString('0198c728-8f2d-7f43-92d8-3f0c75b80186', $uuids),
            new DateTimeImmutable('2026-08-22T08:00:00+00:00'),
        );

        $view = (new OrganizationViewFactory())->fromAggregate($organization);

        self::assertSame('0198c728-a648-75b7-b7d7-c69d0bf84390', $view->id);
        self::assertSame('Zandu Congo', $view->name);
        self::assertSame('ACTIVE', $view->status);
        self::assertSame('CG', $view->countryCode);
        self::assertSame('XAF', $view->defaultCurrency);
        self::assertSame('Africa/Brazzaville', $view->defaultTimeZone);
        self::assertSame('fr_CG', $view->defaultLocale);
        self::assertSame('2026-08-22T08:00:00+00:00', $view->updatedAt);
        self::assertSame(1, $view->version);
    }
}
