<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Organization\Application;

use DateTimeImmutable;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\Organization\Application\Contract\OperationalMode;
use Zandu\Modules\Organization\Application\OrganizationOperationalGuard;
use Zandu\Modules\Organization\Application\StoreOperationalGuard;
use Zandu\Modules\Organization\Domain\CountryCode;
use Zandu\Modules\Organization\Domain\Locale;
use Zandu\Modules\Organization\Domain\Organization;
use Zandu\Modules\Organization\Domain\OrganizationName;
use Zandu\Modules\Organization\Domain\Store\Store;
use Zandu\Modules\Organization\Domain\Store\StoreAddress;
use Zandu\Modules\Organization\Domain\Store\StoreCode;
use Zandu\Modules\Organization\Domain\Store\StoreName;
use Zandu\Modules\Organization\Domain\TimeZone;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\StoreId;
use Zandu\SharedKernel\Money\Currency;

final class OperationalGuardTest extends TestCase
{
    /** @return iterable<string,array{OperationalMode,bool}> */
    public static function suspendedModes(): iterable
    {
        yield 'standard denied' => [OperationalMode::Standard, false];
        yield 'remediation allowed' => [OperationalMode::Remediation, true];
        yield 'termination allowed' => [OperationalMode::Termination, true];
    }

    #[DataProvider('suspendedModes')]
    public function testSuspendedOrganizationPolicy(OperationalMode $mode, bool $allowed): void
    {
        $organization = $this->organization();
        $organization->suspend($this->actorId(), $this->now());

        $this->assertGuardOutcome($allowed, fn() => (new OrganizationOperationalGuard())->assertAllows($organization, $mode));
    }

    #[DataProvider('suspendedModes')]
    public function testSuspendedStorePolicy(OperationalMode $mode, bool $allowed): void
    {
        $store = $this->store();
        $store->suspend($this->actorId(), $this->now());

        $this->assertGuardOutcome($allowed, fn() => (new StoreOperationalGuard())->assertAllows($store, $mode));
    }

    public function testClosedResourcesAlwaysDenyOperations(): void
    {
        $organization = $this->organization();
        $organization->requestClosure($this->actorId(), $this->now());
        $organization->close($this->actorId(), $this->now());

        $this->expectException(LogicException::class);
        (new OrganizationOperationalGuard())->assertAllows($organization, OperationalMode::Termination);
    }

    /** @param callable():void $operation */
    private function assertGuardOutcome(bool $allowed, callable $operation): void
    {
        if (!$allowed) {
            $this->expectException(LogicException::class);
        }
        $operation();
        if ($allowed) {
            self::addToAssertionCount(1);
        }
    }

    private function organization(): Organization
    {
        return Organization::create(
            $this->organizationId(),
            OrganizationName::fromString('Operational organization'),
            CountryCode::fromString('CG'),
            Currency::fromCode('XAF'),
            TimeZone::fromString('Africa/Brazzaville'),
            Locale::fromString('fr_CG'),
            $this->actorId(),
            $this->now(),
        );
    }

    private function store(): Store
    {
        return Store::create(
            StoreId::fromString('0198e212-147c-72d5-b75a-a936797ff9c8', new SymfonyUuidFactory()),
            $this->organizationId(),
            StoreCode::fromString('OPS'),
            StoreName::fromString('Operational store'),
            StoreAddress::fromString('Brazzaville'),
            TimeZone::fromString('Africa/Brazzaville'),
            Currency::fromCode('XAF'),
            Locale::fromString('fr_CG'),
            Currency::fromCode('XAF'),
            $this->actorId(),
            $this->now(),
        );
    }

    private function organizationId(): OrganizationId
    {
        return OrganizationId::fromString('0198e211-147c-72d5-b75a-a936797ff9c8', new SymfonyUuidFactory());
    }

    private function actorId(): ActorId
    {
        return ActorId::fromString('0198e213-147c-72d5-b75a-a936797ff9c8', new SymfonyUuidFactory());
    }

    private function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-08-23T10:00:00+00:00');
    }
}
