<?php

declare(strict_types=1);

namespace Zandu\Modules\Pricing\Infrastructure\Persistence\Orm;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Zandu\Modules\Pricing\Domain\ProductPrice\ProductPrice;

#[ORM\Entity]
#[ORM\Table(name: 'product_prices', schema: 'pricing')]
final class ProductPriceRecord
{
    private function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'guid')]
        private string $id,
        #[ORM\Column(type: 'guid')]
        private string $organizationId,
        #[ORM\Column(type: 'guid')]
        private string $priceListId,
        #[ORM\Column(type: 'guid')]
        private string $productId,
        #[ORM\Column(type: 'guid')]
        private string $packagingId,
        #[ORM\Column(type: 'decimal', precision: 30, scale: 12)]
        private string $amount,
        #[ORM\Column(length: 3)]
        private string $currency,
        #[ORM\Column(length: 16)]
        private string $status,
        #[ORM\Column(type: 'datetimetz_immutable', nullable: true)]
        private ?DateTimeImmutable $validFrom,
        #[ORM\Column(type: 'datetimetz_immutable', nullable: true)]
        private ?DateTimeImmutable $validTo,
        #[ORM\Column(type: 'datetimetz_immutable')]
        private DateTimeImmutable $createdAt,
        #[ORM\Column(type: 'guid')]
        private string $createdBy,
        #[ORM\Version]
        #[ORM\Column(type: 'integer')]
        private int $version,
    ) {}

    public static function fromAggregate(ProductPrice $price): self
    {
        return new self(
            $price->id()->toString(),
            $price->organizationId()->toString(),
            $price->priceListId()->toString(),
            $price->productId()->toString(),
            $price->packagingId()->toString(),
            $price->amount()->amount()->toString(),
            $price->amount()->currency()->code(),
            $price->status()->value,
            $price->validFrom(),
            $price->validTo(),
            $price->createdAt(),
            $price->createdBy()->toString(),
            $price->version(),
        );
    }

    public function synchronize(ProductPrice $price): void
    {
        $this->amount = $price->amount()->amount()->toString();
        $this->status = $price->status()->value;
        $this->validFrom = $price->validFrom();
        $this->validTo = $price->validTo();
    }

    public function id(): string
    {
        return $this->id;
    }
    public function organizationId(): string
    {
        return $this->organizationId;
    }
    public function priceListId(): string
    {
        return $this->priceListId;
    }
    public function productId(): string
    {
        return $this->productId;
    }
    public function packagingId(): string
    {
        return $this->packagingId;
    }
    public function amount(): string
    {
        return $this->amount;
    }
    public function currency(): string
    {
        return $this->currency;
    }
    public function status(): string
    {
        return $this->status;
    }
    public function validFrom(): ?DateTimeImmutable
    {
        return $this->validFrom;
    }
    public function validTo(): ?DateTimeImmutable
    {
        return $this->validTo;
    }
    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
    public function createdBy(): string
    {
        return $this->createdBy;
    }
    public function version(): int
    {
        return $this->version;
    }
}
