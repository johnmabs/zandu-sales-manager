<?php

declare(strict_types=1);

namespace Zandu\Modules\Pricing\Domain\ProductPrice;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use LogicException;
use Zandu\Modules\Pricing\Domain\PriceList\PriceList;
use Zandu\Modules\Pricing\Domain\ProductPrice\Event\ProductPriceActivated;
use Zandu\Modules\Pricing\Domain\ProductPrice\Event\ProductPriceArchived;
use Zandu\Modules\Pricing\Domain\ProductPrice\Event\ProductPriceCreated;
use Zandu\Modules\Pricing\Domain\ProductPrice\Event\ProductPriceDeactivated;
use Zandu\Modules\Pricing\Domain\ProductPrice\Event\ProductPriceEvent;
use Zandu\Modules\Pricing\Domain\ProductPrice\Event\ProductPriceUpdated;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\PriceListId;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\ProductPackagingId;
use Zandu\SharedKernel\Identity\ProductPriceId;
use Zandu\SharedKernel\Money\Money;
use Zandu\SharedKernel\Versioning\{TracksAggregateVersion, VersionedAggregate};

final class ProductPrice implements VersionedAggregate
{
    use TracksAggregateVersion;

    /** @var list<ProductPriceEvent> */
    private array $recordedEvents = [];

    private function __construct(
        private readonly ProductPriceId $id,
        private readonly OrganizationId $organizationId,
        private readonly PriceListId $priceListId,
        private readonly ProductId $productId,
        private readonly ProductPackagingId $packagingId,
        private Money $amount,
        private ProductPriceStatus $status,
        private ?DateTimeImmutable $validFrom,
        private ?DateTimeImmutable $validTo,
        private readonly DateTimeImmutable $createdAt,
        private readonly ActorId $createdBy,
        private int $version,
    ) {
        $this->assertValidVersion();
        self::assertAmount($amount);
        self::assertPeriod($validFrom, $validTo);
    }

    public static function createActive(
        ProductPriceId $id,
        PriceList $priceList,
        ProductPriceTarget $target,
        Money $amount,
        ?DateTimeImmutable $validFrom,
        ?DateTimeImmutable $validTo,
        ActorId $actorId,
        DateTimeImmutable $occurredAt,
    ): self {
        self::assertOwnershipAndCurrency($priceList, $target, $amount);
        $occurredAt = self::utc($occurredAt);
        $productPrice = new self(
            $id,
            $priceList->organizationId(),
            $priceList->id(),
            $target->productId(),
            $target->packagingId(),
            $amount,
            ProductPriceStatus::Active,
            null !== $validFrom ? self::utc($validFrom) : null,
            null !== $validTo ? self::utc($validTo) : null,
            $occurredAt,
            $actorId,
            1,
        );
        $productPrice->recordedEvents[] = new ProductPriceCreated($productPrice->organizationId, $id, $actorId, $occurredAt);

        return $productPrice;
    }

    public static function reconstitute(
        ProductPriceId $id,
        OrganizationId $organizationId,
        PriceListId $priceListId,
        ProductId $productId,
        ProductPackagingId $packagingId,
        Money $amount,
        ProductPriceStatus $status,
        ?DateTimeImmutable $validFrom,
        ?DateTimeImmutable $validTo,
        DateTimeImmutable $createdAt,
        ActorId $createdBy,
        int $version,
    ): self {
        return new self(
            $id,
            $organizationId,
            $priceListId,
            $productId,
            $packagingId,
            $amount,
            $status,
            null !== $validFrom ? self::utc($validFrom) : null,
            null !== $validTo ? self::utc($validTo) : null,
            self::utc($createdAt),
            $createdBy,
            $version,
        );
    }

    public function update(
        PriceList $priceList,
        Money $amount,
        ?DateTimeImmutable $validFrom,
        ?DateTimeImmutable $validTo,
        ActorId $actorId,
        DateTimeImmutable $occurredAt,
    ): void {
        $this->requireNotArchived('An archived product price cannot be updated.');
        if (!$priceList->id()->equals($this->priceListId)
            || !$priceList->organizationId()->equals($this->organizationId)
            || !$priceList->currency()->equals($amount->currency())) {
            throw new LogicException('Product price must retain its price list tenant and currency.');
        }
        self::assertAmount($amount);
        $validFrom = null !== $validFrom ? self::utc($validFrom) : null;
        $validTo = null !== $validTo ? self::utc($validTo) : null;
        self::assertPeriod($validFrom, $validTo);
        $this->amount = $amount;
        $this->validFrom = $validFrom;
        $this->validTo = $validTo;
        $occurredAt = $this->changedAt($occurredAt);
        $this->recordedEvents[] = new ProductPriceUpdated($this->organizationId, $this->id, $actorId, $occurredAt);
    }

    public function activate(ActorId $actorId, DateTimeImmutable $occurredAt): void
    {
        if (ProductPriceStatus::Inactive !== $this->status) {
            throw new LogicException('Only an inactive product price can be activated.');
        }
        $this->status = ProductPriceStatus::Active;
        $occurredAt = $this->changedAt($occurredAt);
        $this->recordedEvents[] = new ProductPriceActivated($this->organizationId, $this->id, $actorId, $occurredAt);
    }

    public function deactivate(ActorId $actorId, DateTimeImmutable $occurredAt): void
    {
        if (ProductPriceStatus::Active !== $this->status) {
            throw new LogicException('Only an active product price can be deactivated.');
        }
        $this->status = ProductPriceStatus::Inactive;
        $occurredAt = $this->changedAt($occurredAt);
        $this->recordedEvents[] = new ProductPriceDeactivated($this->organizationId, $this->id, $actorId, $occurredAt);
    }

    public function archive(ActorId $actorId, DateTimeImmutable $occurredAt): void
    {
        $this->requireNotArchived('Product price is already archived.');
        $this->status = ProductPriceStatus::Archived;
        $occurredAt = $this->changedAt($occurredAt);
        $this->recordedEvents[] = new ProductPriceArchived($this->organizationId, $this->id, $actorId, $occurredAt);
    }

    public function isEffectiveAt(DateTimeImmutable $businessInstant): bool
    {
        $businessInstant = self::utc($businessInstant);

        return ProductPriceStatus::Active === $this->status
            && (null === $this->validFrom || $businessInstant >= $this->validFrom)
            && (null === $this->validTo || $businessInstant <= $this->validTo);
    }

    /** @return list<ProductPriceEvent> */
    public function releaseEvents(): array
    {
        $events = $this->recordedEvents;
        $this->recordedEvents = [];

        return $events;
    }

    public function id(): ProductPriceId
    {
        return $this->id;
    }
    public function organizationId(): OrganizationId
    {
        return $this->organizationId;
    }
    public function priceListId(): PriceListId
    {
        return $this->priceListId;
    }
    public function productId(): ProductId
    {
        return $this->productId;
    }
    public function packagingId(): ProductPackagingId
    {
        return $this->packagingId;
    }
    public function amount(): Money
    {
        return $this->amount;
    }
    public function status(): ProductPriceStatus
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
    public function createdBy(): ActorId
    {
        return $this->createdBy;
    }

    private static function assertOwnershipAndCurrency(PriceList $priceList, ProductPriceTarget $target, Money $amount): void
    {
        if (!$priceList->organizationId()->equals($target->organizationId())) {
            throw new LogicException('Product price target must belong to the price list tenant.');
        }
        if (!$priceList->currency()->equals($amount->currency())) {
            throw new LogicException('Product price currency must match the price list currency.');
        }
    }

    private static function assertAmount(Money $amount): void
    {
        if ($amount->amount()->isNegative()) {
            throw new InvalidArgumentException('Product price amount must be non-negative.');
        }
    }

    private static function assertPeriod(?DateTimeImmutable $validFrom, ?DateTimeImmutable $validTo): void
    {
        if (null !== $validFrom && null !== $validTo && $validTo < $validFrom) {
            throw new InvalidArgumentException('Product price validity end must not precede its start.');
        }
    }

    private function changedAt(DateTimeImmutable $occurredAt): DateTimeImmutable
    {
        $this->advanceVersion();

        return self::utc($occurredAt);
    }

    private function requireNotArchived(string $message): void
    {
        if (ProductPriceStatus::Archived === $this->status) {
            throw new LogicException($message);
        }
    }

    private static function utc(DateTimeImmutable $dateTime): DateTimeImmutable
    {
        return $dateTime->setTimezone(new DateTimeZone('UTC'));
    }
}
