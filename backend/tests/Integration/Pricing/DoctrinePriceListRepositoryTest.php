<?php

declare(strict_types=1);

namespace Zandu\Tests\Integration\Pricing;

use DateTimeImmutable;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zandu\Modules\Pricing\Domain\PriceList\PriceList;
use Zandu\Modules\Pricing\Domain\PriceList\PriceListCode;
use Zandu\Modules\Pricing\Domain\PriceList\PriceListName;
use Zandu\Modules\Pricing\Domain\PriceList\PriceListPriority;
use Zandu\Modules\Pricing\Domain\PriceList\PriceListStatus;
use Zandu\Modules\Pricing\Infrastructure\Persistence\Orm\DoctrinePriceListRepository;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\Platform\Persistence\DoctrineTenantTransaction;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\PriceListId;
use Zandu\SharedKernel\Money\Currency;

final class DoctrinePriceListRepositoryTest extends KernelTestCase
{
    private const ORGANIZATION_A = '0198e3a1-b2a4-7b6e-8e0e-608484906502';
    private const ORGANIZATION_B = '0198e3a2-b2a4-7b6e-8e0e-608484906502';
    private const PRICE_LIST_A = '0198e3b1-147c-72d5-b75a-a936797ff9c8';
    private const PRICE_LIST_B = '0198e3b2-147c-72d5-b75a-a936797ff9c8';
    private const ACTOR = '0198c728-8f2d-7f43-92d8-3f0c75b80186';

    private EntityManagerInterface $em;
    private DoctrinePriceListRepository $repository;
    private DoctrineTenantTransaction $transaction;
    private SymfonyUuidFactory $ids;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->ids = new SymfonyUuidFactory();
        $this->repository = new DoctrinePriceListRepository($this->em, $this->ids);
        $this->transaction = new DoctrineTenantTransaction($this->em->getConnection(), 'zandu_runtime');
        $this->cleanup();
        $this->insertOrganization(self::ORGANIZATION_A, 'Pricing tenant A');
        $this->insertOrganization(self::ORGANIZATION_B, 'Pricing tenant B');
    }

    protected function tearDown(): void
    {
        $this->em->clear();
        $this->cleanup();
        parent::tearDown();
    }

    public function testPriceListRoundTripsAndUpdatesWithOptimisticVersion(): void
    {
        $priceList = $this->priceList(self::PRICE_LIST_A, self::ORGANIZATION_A, 'RETAIL');
        $organizationId = $priceList->organizationId();
        $this->transaction->transactional($organizationId, fn() => $this->repository->save($priceList));

        $priceList->activate($this->actorId(), new DateTimeImmutable('2026-08-26T11:00:00Z'));
        $this->transaction->transactional($organizationId, fn() => $this->repository->save($priceList));
        $this->em->clear();

        $restored = $this->transaction->transactional(
            $organizationId,
            fn(): PriceList => $this->repository->get($organizationId, $priceList->id()),
        );

        self::assertSame('RETAIL', $restored->code()->value());
        self::assertSame('XAF', $restored->currency()->code());
        self::assertSame(PriceListStatus::Active, $restored->status());
        self::assertSame('2026-08-26T10:00:00+00:00', $restored->validFrom()?->format(DATE_ATOM));
        self::assertSame(2, $restored->version());
        self::assertNotNull($this->transaction->transactional(
            $organizationId,
            fn(): ?PriceList => $this->repository->findByCode($organizationId, PriceListCode::fromString('retail')),
        ));
    }

    public function testCodeIsUniqueWithinAnOrganization(): void
    {
        $organizationId = $this->organizationId(self::ORGANIZATION_A);
        $this->transaction->transactional($organizationId, fn() => $this->repository->save($this->priceList(self::PRICE_LIST_A, self::ORGANIZATION_A, 'RETAIL')));

        $this->expectException(UniqueConstraintViolationException::class);
        $this->transaction->transactional($organizationId, fn() => $this->repository->save($this->priceList(self::PRICE_LIST_B, self::ORGANIZATION_A, 'RETAIL')));
    }

    public function testSameCodeIsAllowedAcrossTenantsWithoutVisibilityLeak(): void
    {
        $organizationA = $this->organizationId(self::ORGANIZATION_A);
        $organizationB = $this->organizationId(self::ORGANIZATION_B);
        $priceListA = $this->priceList(self::PRICE_LIST_A, self::ORGANIZATION_A, 'RETAIL');
        $priceListB = $this->priceList(self::PRICE_LIST_B, self::ORGANIZATION_B, 'RETAIL');
        $this->transaction->transactional($organizationA, fn() => $this->repository->save($priceListA));
        $this->em->clear();
        $this->transaction->transactional($organizationB, fn() => $this->repository->save($priceListB));
        $this->em->clear();

        self::assertNull($this->transaction->transactional(
            $organizationB,
            fn(): ?PriceList => $this->repository->find($organizationB, $priceListA->id()),
        ));
        self::assertSame(
            self::PRICE_LIST_B,
            $this->transaction->transactional(
                $organizationB,
                fn(): ?string => $this->repository->findByCode($organizationB, PriceListCode::fromString('RETAIL'))?->id()->toString(),
            ),
        );
    }

    private function priceList(string $id, string $organizationId, string $code): PriceList
    {
        return PriceList::createDraft(
            PriceListId::fromString($id, $this->ids),
            $this->organizationId($organizationId),
            PriceListCode::fromString($code),
            PriceListName::fromString('Tarif standard'),
            Currency::fromCode('XAF'),
            new DateTimeImmutable('2026-08-26T11:00:00+01:00'),
            null,
            PriceListPriority::fromInt(0),
            $this->actorId(),
            new DateTimeImmutable('2026-08-26T09:00:00Z'),
        );
    }

    private function organizationId(string $id): OrganizationId
    {
        return OrganizationId::fromString($id, $this->ids);
    }

    private function actorId(): ActorId
    {
        return ActorId::fromString(self::ACTOR, $this->ids);
    }

    private function insertOrganization(string $id, string $name): void
    {
        $this->em->getConnection()->executeStatement(
            "INSERT INTO organization.organizations (id,name,status,country_code,default_currency,default_time_zone,default_locale,created_by,created_at,updated_by,updated_at,version) VALUES (?,?,'ACTIVE','CG','XAF','Africa/Brazzaville','fr_CG',?,NOW(),?,NOW(),1)",
            [$id, $name, self::ACTOR, self::ACTOR],
        );
    }

    private function cleanup(): void
    {
        $connection = $this->em->getConnection();
        $connection->executeStatement('DELETE FROM pricing.price_lists WHERE organization_id IN (?, ?)', [self::ORGANIZATION_A, self::ORGANIZATION_B]);
        $connection->executeStatement('DELETE FROM organization.organizations WHERE id IN (?, ?)', [self::ORGANIZATION_A, self::ORGANIZATION_B]);
    }
}
