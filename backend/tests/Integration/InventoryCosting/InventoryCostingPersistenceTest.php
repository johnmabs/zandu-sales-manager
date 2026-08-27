<?php

declare(strict_types=1);

namespace Zandu\Tests\Integration\InventoryCosting;

use DateTimeImmutable;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zandu\Modules\InventoryCosting\Domain\Valuation\{MovingWeightedAverageCalculator, StockValuation, StockValuationRepository};
use Zandu\Modules\InventoryCosting\Domain\ValuationMovement\{StockValuationMovement, StockValuationMovementRepository, StockValuationMovementSource, StockValuationMovementType};
use Zandu\Modules\InventoryCosting\Infrastructure\Persistence\Orm\{DoctrineStockValuationMovementRepository, DoctrineStockValuationRepository};
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\Platform\Persistence\DoctrineTenantTransaction;
use Zandu\SharedKernel\Identity\{OrganizationId, ProductId, StockId, StockMovementId, StockValuationId, StockValuationMovementId, StoreId};
use Zandu\SharedKernel\Messaging\CorrelationId;
use Zandu\SharedKernel\Money\{Currency, Money};
use Zandu\SharedKernel\Quantity\Quantity;

final class InventoryCostingPersistenceTest extends KernelTestCase
{
    private const ORGANIZATION_A = '0198f301-1111-7111-8111-111111111111';
    private const ORGANIZATION_B = '0198f302-1111-7111-8111-111111111111';
    private const STORE_A = '0198f303-1111-7111-8111-111111111111';
    private const STORE_B = '0198f304-1111-7111-8111-111111111111';
    private const UNIT_A = '0198f305-1111-7111-8111-111111111111';
    private const UNIT_B = '0198f306-1111-7111-8111-111111111111';
    private const PRODUCT_A = '0198f307-1111-7111-8111-111111111111';
    private const PRODUCT_B = '0198f308-1111-7111-8111-111111111111';
    private const STOCK_A = '0198f309-1111-7111-8111-111111111111';
    private const STOCK_B = '0198f30a-1111-7111-8111-111111111111';
    private const VALUATION_A = '0198f30b-1111-7111-8111-111111111111';
    private const VALUATION_B = '0198f30c-1111-7111-8111-111111111111';
    private const STOCK_MOVEMENT_A = '0198f30d-1111-7111-8111-111111111111';
    private const ACTOR = '0198c728-8f2d-7f43-92d8-3f0c75b80186';
    private const CORRELATION = '0198f30e-1111-7111-8111-111111111111';

    private EntityManagerInterface $em;
    private StockValuationRepository $valuations;
    private StockValuationMovementRepository $movements;
    private DoctrineTenantTransaction $transaction;
    private SymfonyUuidFactory $ids;
    private BrickDecimalFactory $decimals;
    private MovingWeightedAverageCalculator $calculator;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->transaction = new DoctrineTenantTransaction($this->em->getConnection(), 'zandu_runtime');
        $this->ids = new SymfonyUuidFactory();
        $this->decimals = new BrickDecimalFactory();
        $this->valuations = new DoctrineStockValuationRepository($this->em, $this->ids, $this->decimals);
        $this->movements = new DoctrineStockValuationMovementRepository($this->em, $this->ids, $this->decimals);
        $this->calculator = new MovingWeightedAverageCalculator();
        $this->cleanup();
        $this->fixtures();
    }

    protected function tearDown(): void
    {
        if ($this->em->isOpen()) {
            $this->em->clear();
        }
        $this->cleanup();
        parent::tearDown();
    }

    public function testValuationAndOpeningLedgerRoundTripWithExactDecimals(): void
    {
        $organization = $this->organization(self::ORGANIZATION_A);
        $valuation = $this->valuationA();
        $opening = $this->movement(
            '0198f311-1111-7111-8111-111111111111',
            StockValuationMovementType::Opening,
            null,
            '10',
            '4000',
            '40000',
            '0',
            '40000',
            '0',
            '4000',
        );

        $inserted = $this->transaction->transactional($organization, function () use ($valuation, $opening): array {
            $this->valuations->save($valuation);

            return [$this->movements->appendOnce($opening), $this->movements->appendOnce($opening)];
        });
        self::assertSame([true, false], $inserted);
        $this->em->clear();

        [$restored, $ledger] = $this->transaction->transactional($organization, fn(): array => [
            $this->valuations->getByStockForUpdate($organization, $this->stock(self::STOCK_A)),
            $this->movements->findByValuation($organization, $this->valuationId(self::VALUATION_A)),
        ]);

        self::assertSame('10.000000000000', $restored->quantityOnHand()->toString());
        self::assertSame('40000.000000', $restored->totalValue()->amount()->toString());
        self::assertSame(1, $restored->version());
        self::assertCount(1, $ledger);
        self::assertSame('4000.000000000000', $ledger[0]->resultingAverageCost()->amount()->toString());

        $restored->receive($this->quantity('10'), $this->money('6000'), $this->calculator);
        $this->transaction->transactional($organization, fn() => $this->valuations->save($restored));
        $this->em->clear();

        $updated = $this->transaction->transactional(
            $organization,
            fn(): StockValuation => $this->valuations->getByStock($organization, $this->stock(self::STOCK_A)),
        );
        self::assertSame('20.000000000000', $updated->quantityOnHand()->toString());
        self::assertSame('100000.000000', $updated->totalValue()->amount()->toString());
        self::assertSame(2, $updated->version());
    }

    public function testAStockCanHaveOnlyOneValuation(): void
    {
        $organization = $this->organization(self::ORGANIZATION_A);
        $this->transaction->transactional($organization, fn() => $this->valuations->save($this->valuationA()));
        $duplicate = StockValuation::initialize(
            $this->valuationId('0198f312-1111-7111-8111-111111111111'),
            $organization,
            $this->store(self::STORE_A),
            $this->product(self::PRODUCT_A),
            $this->stock(self::STOCK_A),
            $this->quantity('10'),
            $this->money('4000'),
            $this->calculator,
        );

        $this->expectException(UniqueConstraintViolationException::class);
        $this->transaction->transactional($organization, fn() => $this->valuations->save($duplicate));
    }

    public function testOptimisticVersionRejectsAStaleAggregate(): void
    {
        $organization = $this->organization(self::ORGANIZATION_A);
        $this->transaction->transactional($organization, fn() => $this->valuations->save($this->valuationA()));
        $this->em->clear();
        $first = $this->transaction->transactional($organization, fn(): StockValuation => $this->valuations->getByStock($organization, $this->stock(self::STOCK_A)));
        $second = $this->transaction->transactional($organization, fn(): StockValuation => $this->valuations->getByStock($organization, $this->stock(self::STOCK_A)));
        $first->receive($this->quantity('1'), $this->money('4000'), $this->calculator);
        $second->receive($this->quantity('1'), $this->money('4000'), $this->calculator);
        $this->transaction->transactional($organization, fn() => $this->valuations->save($first));

        $this->expectException(OptimisticLockException::class);
        $this->transaction->transactional($organization, fn() => $this->valuations->save($second));
    }

    public function testOnePhysicalMovementCanBeValuedOnlyOnce(): void
    {
        $organization = $this->organization(self::ORGANIZATION_A);
        $this->transaction->transactional($organization, fn() => $this->valuations->save($this->valuationA()));
        $this->insertPhysicalMovement();
        $first = $this->movement('0198f313-1111-7111-8111-111111111111', StockValuationMovementType::AdjustmentIn, $this->stockMovement(), '1', '4000', '4000', '40000', '44000', '4000', '4000');
        $retry = $this->movement('0198f314-1111-7111-8111-111111111111', StockValuationMovementType::AdjustmentIn, $this->stockMovement(), '1', '4000', '4000', '40000', '44000', '4000', '4000');

        $result = $this->transaction->transactional($organization, fn(): array => [
            $this->movements->appendOnce($first),
            $this->movements->appendOnce($retry),
        ]);

        self::assertSame([true, false], $result);
    }

    public function testRuntimeTenantCannotReadAnotherTenantValuationOrLedger(): void
    {
        $organizationB = $this->organization(self::ORGANIZATION_B);
        $valuationB = StockValuation::initialize(
            $this->valuationId(self::VALUATION_B),
            $organizationB,
            $this->store(self::STORE_B),
            $this->product(self::PRODUCT_B),
            $this->stock(self::STOCK_B),
            $this->quantity('5'),
            $this->money('2000'),
            $this->calculator,
        );
        $this->transaction->transactional($organizationB, fn() => $this->valuations->save($valuationB));
        $this->em->clear();

        $visibleFromA = $this->transaction->transactional(
            $this->organization(self::ORGANIZATION_A),
            fn(): array => [
                $this->valuations->findByStock($organizationB, $this->stock(self::STOCK_B)),
                $this->movements->findByValuation($organizationB, $this->valuationId(self::VALUATION_B)),
            ],
        );

        self::assertNull($visibleFromA[0]);
        self::assertSame([], $visibleFromA[1]);
    }

    private function valuationA(): StockValuation
    {
        return StockValuation::initialize(
            $this->valuationId(self::VALUATION_A),
            $this->organization(self::ORGANIZATION_A),
            $this->store(self::STORE_A),
            $this->product(self::PRODUCT_A),
            $this->stock(self::STOCK_A),
            $this->quantity('10'),
            $this->money('4000'),
            $this->calculator,
        );
    }

    private function movement(
        string $id,
        StockValuationMovementType $type,
        ?StockMovementId $stockMovementId,
        string $quantity,
        string $unitCost,
        string $value,
        string $previousTotal,
        string $resultingTotal,
        string $previousAverage,
        string $resultingAverage,
    ): StockValuationMovement {
        return StockValuationMovement::record(
            StockValuationMovementId::fromString($id, $this->ids),
            $this->valuationId(self::VALUATION_A),
            $this->organization(self::ORGANIZATION_A),
            $this->store(self::STORE_A),
            $this->product(self::PRODUCT_A),
            $this->stock(self::STOCK_A),
            $stockMovementId,
            $type,
            $this->quantity($quantity),
            $this->money($unitCost),
            $this->money($value),
            $this->money($previousTotal),
            $this->money($resultingTotal),
            $this->money($previousAverage),
            $this->money($resultingAverage),
            StockValuationMovementSource::from($type->sourceType(), 'fixture'),
            new DateTimeImmutable('2026-08-27T12:00:00Z'),
            CorrelationId::fromString(self::CORRELATION, $this->ids),
        );
    }

    private function fixtures(): void
    {
        $this->fixture(self::ORGANIZATION_A, self::STORE_A, self::UNIT_A, self::PRODUCT_A, self::STOCK_A, 'A');
        $this->fixture(self::ORGANIZATION_B, self::STORE_B, self::UNIT_B, self::PRODUCT_B, self::STOCK_B, 'B');
    }

    private function fixture(string $organization, string $store, string $unit, string $product, string $stock, string $suffix): void
    {
        $db = $this->em->getConnection();
        $db->executeStatement("INSERT INTO organization.organizations (id,name,status,country_code,default_currency,default_time_zone,default_locale,created_by,created_at,updated_by,updated_at,version) VALUES (?,?,'ACTIVE','CG','XAF','Africa/Brazzaville','fr_CG',?,NOW(),?,NOW(),1)", [$organization, "Costing $suffix", self::ACTOR, self::ACTOR]);
        $db->executeStatement("INSERT INTO organization.stores (id,organization_id,code,name,status,time_zone,currency,locale,created_by,created_at,updated_by,updated_at,version) VALUES (?,?,?,?,'ACTIVE','Africa/Brazzaville','XAF','fr_CG',?,NOW(),?,NOW(),1)", [$store, $organization, "STORE-$suffix", "Store $suffix", self::ACTOR, self::ACTOR]);
        $db->executeStatement("INSERT INTO catalog.units_of_measure (id,organization_id,code,name,dimension,precision,rounding_mode,status,version) VALUES (?,?,?,?,'COUNT',0,'HalfUp','ACTIVE',1)", [$unit, $organization, "EA-$suffix", "Unit $suffix"]);
        $db->executeStatement("INSERT INTO catalog.products (id,organization_id,product_code,name,status,type,base_unit_id,inventory_tracked,created_at,created_by,activated_at,activated_by,version) VALUES (?,?,?,?,'ACTIVE','PHYSICAL',?,TRUE,NOW(),?,NOW(),?,1)", [$product, $organization, "SKU-$suffix", "Product $suffix", $unit, self::ACTOR, self::ACTOR]);
        $db->executeStatement('INSERT INTO inventory.stock (id,organization_id,store_id,product_id,quantity_on_hand,initialized,initialized_at,initialized_by,version) VALUES (?,?,?,?,10,TRUE,NOW(),?,1)', [$stock, $organization, $store, $product, self::ACTOR]);
    }

    private function insertPhysicalMovement(): void
    {
        $this->em->getConnection()->executeStatement(
            "INSERT INTO inventory.stock_movement (id,organization_id,store_id,product_id,stock_id,type,quantity,previous_quantity,resulting_quantity,source_type,source_reference_id,reason,performed_by,occurred_at) VALUES (?,?,?,?,?,'ADJUSTMENT_IN',1,10,11,'MANUAL_ADJUSTMENT',NULL,'Costing test',?,NOW())",
            [self::STOCK_MOVEMENT_A, self::ORGANIZATION_A, self::STORE_A, self::PRODUCT_A, self::STOCK_A, self::ACTOR],
        );
    }

    private function cleanup(): void
    {
        $db = $this->em->getConnection();
        $organizations = [self::ORGANIZATION_A, self::ORGANIZATION_B];
        $db->executeStatement('DELETE FROM inventory_costing.stock_valuation_movement WHERE organization_id IN (?, ?)', $organizations);
        $db->executeStatement('DELETE FROM inventory_costing.stock_valuation WHERE organization_id IN (?, ?)', $organizations);
        $db->executeStatement('DELETE FROM inventory.stock_movement WHERE organization_id IN (?, ?)', $organizations);
        $db->executeStatement('DELETE FROM inventory.stock WHERE organization_id IN (?, ?)', $organizations);
        $db->executeStatement('DELETE FROM catalog.products WHERE organization_id IN (?, ?)', $organizations);
        $db->executeStatement('DELETE FROM catalog.units_of_measure WHERE organization_id IN (?, ?)', $organizations);
        $db->executeStatement('DELETE FROM organization.stores WHERE organization_id IN (?, ?)', $organizations);
        $db->executeStatement('DELETE FROM organization.organizations WHERE id IN (?, ?)', $organizations);
    }

    private function organization(string $id): OrganizationId
    {
        return OrganizationId::fromString($id, $this->ids);
    }
    private function store(string $id): StoreId
    {
        return StoreId::fromString($id, $this->ids);
    }
    private function product(string $id): ProductId
    {
        return ProductId::fromString($id, $this->ids);
    }
    private function stock(string $id): StockId
    {
        return StockId::fromString($id, $this->ids);
    }
    private function valuationId(string $id): StockValuationId
    {
        return StockValuationId::fromString($id, $this->ids);
    }
    private function stockMovement(): StockMovementId
    {
        return StockMovementId::fromString(self::STOCK_MOVEMENT_A, $this->ids);
    }
    private function quantity(string $value): Quantity
    {
        return Quantity::fromString($value, $this->decimals);
    }
    private function money(string $value): Money
    {
        return Money::fromString($value, Currency::fromCode('XAF'), $this->decimals);
    }
}
