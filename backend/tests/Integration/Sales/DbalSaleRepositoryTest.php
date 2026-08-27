<?php

declare(strict_types=1);

namespace Zandu\Tests\Integration\Sales;

use DateTimeImmutable;
use Zandu\Modules\Sales\Domain\{Sale,SaleLine};
use Zandu\Modules\Sales\Infrastructure\Persistence\DbalSaleRepository;
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\Platform\Persistence\DoctrineTenantTransaction;
use Zandu\SharedKernel\Context\{ActorContext,ActorType};
use Zandu\SharedKernel\Identity\{ActorId,OrganizationId,ProductId,ProductPackagingId,SaleId,SaleLineId,StoreId,UnitOfMeasureId};
use Zandu\SharedKernel\Messaging\CorrelationId;
use Zandu\SharedKernel\Money\{Currency,Money};
use Zandu\SharedKernel\Quantity\Quantity;
use Zandu\Tests\Integration\PostgresTestCase;

final class DbalSaleRepositoryTest extends PostgresTestCase
{
    private const ORGANIZATION = '0198fb01-1111-7111-8111-111111111111';
    private const STORE = '0198fb02-1111-7111-8111-111111111111';
    private const SALE = '0198fb03-1111-7111-8111-111111111111';
    private const LINE = '0198fb04-1111-7111-8111-111111111111';
    private const ACTOR = '0198fb05-1111-7111-8111-111111111111';

    protected function setUp(): void
    {
        parent::setUp();
        $this->cleanup();
        $this->connection->executeStatement("INSERT INTO organization.organizations (id,name,status,country_code,default_currency,default_time_zone,default_locale,created_by,created_at,updated_by,updated_at,version) VALUES (?, 'Sales repository', 'ACTIVE','CG','XAF','Africa/Brazzaville','fr_CG',?,NOW(),?,NOW(),1)", [self::ORGANIZATION, self::ACTOR, self::ACTOR]);
        $this->connection->executeStatement("INSERT INTO organization.stores (id,organization_id,code,name,status,time_zone,currency,locale,created_by,created_at,updated_by,updated_at,version) VALUES (?,?,'MAIN','Main','ACTIVE','Africa/Brazzaville','XAF','fr_CG',?,NOW(),?,NOW(),1)", [self::STORE, self::ORGANIZATION, self::ACTOR, self::ACTOR]);
    }

    protected function tearDown(): void
    {
        $this->cleanup();
        parent::tearDown();
    }

    public function testDraftLineEditsRoundTripWithoutLosingSnapshots(): void
    {
        $ids = new SymfonyUuidFactory();
        $decimals = new BrickDecimalFactory();
        $repository = new DbalSaleRepository($this->connection, $ids, $decimals);
        $transaction = new DoctrineTenantTransaction($this->connection, 'zandu_runtime');
        $organization = OrganizationId::fromString(self::ORGANIZATION, $ids);
        $saleId = SaleId::fromString(self::SALE, $ids);
        $actor = new ActorContext(ActorId::fromString(self::ACTOR, $ids), $organization, ActorType::User, CorrelationId::fromString('0198fb06-1111-7111-8111-111111111111', $ids), new DateTimeImmutable('2026-08-27T10:00:00Z'));
        $currency = Currency::fromCode('XAF');
        $sale = Sale::create($saleId, $organization, StoreId::fromString(self::STORE, $ids), 'XAF', Money::fromString('0', $currency, $decimals), $actor, new DateTimeImmutable('2026-08-27T10:00:00Z'));
        $transaction->transactional($organization, fn() => $repository->save($sale));

        $sale = $transaction->transactional($organization, fn(): Sale => $repository->getForUpdate($organization, $saleId));
        $sale->addLine($this->line($sale, '1', '100'));
        $transaction->transactional($organization, fn() => $repository->save($sale));

        $sale = $transaction->transactional($organization, fn(): Sale => $repository->getForUpdate($organization, $saleId));
        self::assertSame('SKU-SNAPSHOT', $sale->lines()[0]->productCodeSnapshot());
        $sale->replaceLine($this->line($sale, '2', '200'));
        $transaction->transactional($organization, fn() => $repository->save($sale));

        $sale = $transaction->transactional($organization, fn(): Sale => $repository->get($organization, $saleId));
        self::assertSame('2.000000000000', $sale->lines()[0]->enteredQuantity()->toString());
        self::assertSame('200.000000000000', $sale->total()->amount()->toString());
        self::assertSame(['product' => 3, 'taxPolicy' => 'NO_TAX'], $sale->lines()[0]->sourceVersions());

        $sale->removeLine(SaleLineId::fromString(self::LINE, $ids));
        $transaction->transactional($organization, fn() => $repository->save($sale));
        self::assertSame(0, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM sales.sale_line WHERE sale_id = ?', [self::SALE]));
    }

    private function line(Sale $sale, string $quantity, string $total): SaleLine
    {
        $ids = new SymfonyUuidFactory();
        $decimals = new BrickDecimalFactory();
        $q = Quantity::fromString($quantity, $decimals);
        $one = Quantity::fromString('1', $decimals);
        $currency = Currency::fromCode('XAF');
        $money = fn(string $amount): Money => Money::fromString($amount, $currency, $decimals);

        return new SaleLine(SaleLineId::fromString(self::LINE, $ids), $sale->id(), ProductId::fromString('0198fb07-1111-7111-8111-111111111111', $ids), ProductPackagingId::fromString('0198fb08-1111-7111-8111-111111111111', $ids), 'SKU-SNAPSHOT', 'Historical product', 'UNIT', 'Unit', UnitOfMeasureId::fromString('0198fb09-1111-7111-8111-111111111111', $ids), $q, $one, $q, $money('100'), null, null, $money('0'), $money($total), $money('0'), $money($total), $money($total), ['product' => 3, 'taxPolicy' => 'NO_TAX']);
    }

    private function cleanup(): void
    {
        $this->connection->executeStatement('DELETE FROM sales.sale_completion_keys WHERE organization_id = ?', [self::ORGANIZATION]);
        $this->connection->executeStatement('DELETE FROM sales.sale_line WHERE organization_id = ?', [self::ORGANIZATION]);
        $this->connection->executeStatement('DELETE FROM sales.sale WHERE organization_id = ?', [self::ORGANIZATION]);
        $this->connection->executeStatement('DELETE FROM organization.stores WHERE organization_id = ?', [self::ORGANIZATION]);
        $this->connection->executeStatement('DELETE FROM organization.organizations WHERE id = ?', [self::ORGANIZATION]);
    }
}
