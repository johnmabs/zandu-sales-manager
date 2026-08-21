<?php

declare(strict_types=1);

namespace Zandu\Tests\Platform\Api\State;

use ApiPlatform\Metadata\Get;
use LogicException;
use PHPUnit\Framework\TestCase;
use Zandu\Platform\Api\State\ApplicationQueryProvider;

final class ApplicationQueryProviderTest extends TestCase
{
    public function testItMapsRequestDataExecutesAQueryAndReturnsAReadModel(): void
    {
        $provider = new TestQueryProvider();

        $readModel = $provider->provide(
            new Get(),
            ['saleId' => 'sale-42'],
            ['locale' => 'fr'],
        );

        self::assertInstanceOf(TestReadModel::class, $readModel);
        self::assertSame('sale-42', $readModel->saleId);
        self::assertSame('fr', $readModel->locale);
    }
}

final readonly class TestQuery
{
    public function __construct(
        public string $saleId,
        public string $locale,
    ) {}
}

final readonly class TestReadModel
{
    public function __construct(
        public string $saleId,
        public string $locale,
    ) {}
}

/**
 * @extends ApplicationQueryProvider<TestQuery, TestReadModel>
 */
final class TestQueryProvider extends ApplicationQueryProvider
{
    protected function createQuery(array $uriVariables, array $context): object
    {
        return new TestQuery(
            (string) ($uriVariables['saleId'] ?? ''),
            (string) ($context['locale'] ?? 'en'),
        );
    }

    protected function handle(object $query): object|array|null
    {
        if (!$query instanceof TestQuery) {
            throw new LogicException('Unexpected query.');
        }

        return new TestReadModel($query->saleId, $query->locale);
    }
}
