<?php

declare(strict_types=1);

namespace Zandu\Modules\Pricing\Infrastructure\Persistence\Orm;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Zandu\Modules\Pricing\Domain\PriceList\PriceList;

#[ORM\Entity]
#[ORM\Table(name: 'price_lists', schema: 'pricing')]
#[ORM\UniqueConstraint(name: 'price_list_tenant_code_unique', columns: ['organization_id', 'code'])]
#[ORM\UniqueConstraint(name: 'price_list_tenant_id_currency_unique', columns: ['organization_id', 'id', 'currency'])]
final class PriceListRecord
{
    private function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'guid')]
        private string $id,
        #[ORM\Column(type: 'guid')]
        private string $organizationId,
        #[ORM\Column(length: 64)]
        private string $code,
        #[ORM\Column(length: 160)]
        private string $name,
        #[ORM\Column(length: 3)]
        private string $currency,
        #[ORM\Column(length: 16)]
        private string $status,
        #[ORM\Column(length: 16)]
        private string $scope,
        #[ORM\Column(type: 'datetimetz_immutable', nullable: true)]
        private ?DateTimeImmutable $validFrom,
        #[ORM\Column(type: 'datetimetz_immutable', nullable: true)]
        private ?DateTimeImmutable $validTo,
        #[ORM\Column(type: 'integer')]
        private int $priority,
        #[ORM\Column(type: 'datetimetz_immutable')]
        private DateTimeImmutable $createdAt,
        #[ORM\Column(type: 'guid')]
        private string $createdBy,
        #[ORM\Version]
        #[ORM\Column(type: 'integer')]
        private int $version,
    ) {}

    public static function fromAggregate(PriceList $priceList): self
    {
        return new self(
            $priceList->id()->toString(),
            $priceList->organizationId()->toString(),
            $priceList->code()->value(),
            $priceList->name()->value(),
            $priceList->currency()->code(),
            $priceList->status()->value,
            $priceList->scope()->value,
            $priceList->validFrom(),
            $priceList->validTo(),
            $priceList->priority()->value(),
            $priceList->createdAt(),
            $priceList->createdBy()->toString(),
            $priceList->version(),
        );
    }

    public function synchronize(PriceList $priceList): void
    {
        $this->code = $priceList->code()->value();
        $this->name = $priceList->name()->value();
        $this->currency = $priceList->currency()->code();
        $this->status = $priceList->status()->value;
        $this->validFrom = $priceList->validFrom();
        $this->validTo = $priceList->validTo();
        $this->priority = $priceList->priority()->value();
    }

    public function id(): string
    {
        return $this->id;
    }
    public function organizationId(): string
    {
        return $this->organizationId;
    }
    public function code(): string
    {
        return $this->code;
    }
    public function name(): string
    {
        return $this->name;
    }
    public function currency(): string
    {
        return $this->currency;
    }
    public function status(): string
    {
        return $this->status;
    }
    public function scope(): string
    {
        return $this->scope;
    }
    public function validFrom(): ?DateTimeImmutable
    {
        return $this->validFrom;
    }
    public function validTo(): ?DateTimeImmutable
    {
        return $this->validTo;
    }
    public function priority(): int
    {
        return $this->priority;
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
