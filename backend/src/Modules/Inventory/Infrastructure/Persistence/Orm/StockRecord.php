<?php
declare(strict_types=1);
namespace Zandu\Modules\Inventory\Infrastructure\Persistence\Orm;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Zandu\Modules\Inventory\Domain\Stock\Stock;
use Zandu\Modules\Inventory\Domain\Stock\StockQuantity;
use Zandu\SharedKernel\Quantity\Quantity;
use Zandu\Platform\Decimal\BrickDecimalFactory;

#[ORM\Entity]
#[ORM\Table(name: 'stock', schema: 'inventory')]
#[ORM\UniqueConstraint(name: 'stock_identity_unique', columns: ['organization_id', 'store_id', 'product_id'])]
final class StockRecord
{
    private function __construct(
        #[ORM\Id] #[ORM\Column(type: 'guid')] private string $id,
        #[ORM\Column(type: 'guid')] private string $organizationId,
        #[ORM\Column(type: 'guid')] private string $storeId,
        #[ORM\Column(type: 'guid')] private string $productId,
        #[ORM\Column(type: 'decimal', precision: 30, scale: 12)] private string $quantityOnHand,
        #[ORM\Column(type: 'boolean')] private bool $initialized,
        #[ORM\Column(type: 'datetimetz_immutable', nullable: true)] private ?DateTimeImmutable $initializedAt,
        #[ORM\Column(type: 'guid', nullable: true)] private ?string $initializedBy,
        #[ORM\Version] #[ORM\Column(type: 'integer')] private int $version,
    ) {}

    public static function fromAggregate(Stock $stock): self
    {
        return new self($stock->id()->toString(), $stock->organizationId()->toString(), $stock->storeId()->toString(), $stock->productId()->toString(), $stock->quantityOnHand()->toString(), $stock->initialized(), $stock->initializedAt(), $stock->initializedBy()?->toString(), $stock->version());
    }
    public function synchronize(Stock $stock): void
    {
        $this->quantityOnHand = $stock->quantityOnHand()->toString(); $this->initialized = $stock->initialized(); $this->initializedAt = $stock->initializedAt(); $this->initializedBy = $stock->initializedBy()?->toString();
    }
    public function id(): string { return $this->id; }
    public function organizationId(): string { return $this->organizationId; }
    public function storeId(): string { return $this->storeId; }
    public function productId(): string { return $this->productId; }
    public function quantityOnHand(): string { return $this->quantityOnHand; }
    public function initialized(): bool { return $this->initialized; }
    public function initializedAt(): ?DateTimeImmutable { return $this->initializedAt; }
    public function initializedBy(): ?string { return $this->initializedBy; }
    public function version(): int { return $this->version; }
}
