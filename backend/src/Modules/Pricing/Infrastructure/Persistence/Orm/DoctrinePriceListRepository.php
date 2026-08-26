<?php

declare(strict_types=1);

namespace Zandu\Modules\Pricing\Infrastructure\Persistence\Orm;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Zandu\Modules\Pricing\Domain\PriceList\PriceList;
use Zandu\Modules\Pricing\Domain\PriceList\PriceListCode;
use Zandu\Modules\Pricing\Domain\PriceList\PriceListName;
use Zandu\Modules\Pricing\Domain\PriceList\PriceListNotFound;
use Zandu\Modules\Pricing\Domain\PriceList\PriceListPriority;
use Zandu\Modules\Pricing\Domain\PriceList\PriceListRepository;
use Zandu\Modules\Pricing\Domain\PriceList\PriceListScope;
use Zandu\Modules\Pricing\Domain\PriceList\PriceListStatus;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\PriceListId;
use Zandu\SharedKernel\Identity\UuidFactory;
use Zandu\SharedKernel\Money\Currency;

final readonly class DoctrinePriceListRepository implements PriceListRepository
{
    public function __construct(private EntityManagerInterface $em, private UuidFactory $uuids) {}

    public function save(PriceList $priceList): void
    {
        $record = $this->em->find(PriceListRecord::class, $priceList->id()->toString());
        if ($record instanceof PriceListRecord) {
            $expected = $priceList->version() - 1;
            if ($record->version() !== $expected) {
                throw OptimisticLockException::lockFailedVersionMismatch($record, $expected, $record->version());
            }
            $record->synchronize($priceList);
        } else {
            $this->em->persist(PriceListRecord::fromAggregate($priceList));
        }
        $this->em->flush();
    }

    public function get(OrganizationId $organizationId, PriceListId $id): PriceList
    {
        return $this->find($organizationId, $id) ?? throw PriceListNotFound::withId($id);
    }

    public function find(OrganizationId $organizationId, PriceListId $id): ?PriceList
    {
        return $this->aggregate($this->em->getRepository(PriceListRecord::class)->findOneBy([
            'id' => $id->toString(),
            'organizationId' => $organizationId->toString(),
        ]));
    }

    public function findByCode(OrganizationId $organizationId, PriceListCode $code): ?PriceList
    {
        return $this->aggregate($this->em->getRepository(PriceListRecord::class)->findOneBy([
            'organizationId' => $organizationId->toString(),
            'code' => $code->value(),
        ]));
    }
    public function findAll(OrganizationId $organizationId): array
    {
        return array_values(array_filter(array_map(fn(mixed $record): ?PriceList => $this->aggregate($record), $this->em->getRepository(PriceListRecord::class)->findBy(['organizationId' => $organizationId->toString()], ['code' => 'ASC']))));
    }

    private function aggregate(mixed $value): ?PriceList
    {
        if (!$value instanceof PriceListRecord) {
            return null;
        }

        return PriceList::reconstitute(
            PriceListId::fromString($value->id(), $this->uuids),
            OrganizationId::fromString($value->organizationId(), $this->uuids),
            PriceListCode::fromString($value->code()),
            PriceListName::fromString($value->name()),
            Currency::fromCode($value->currency()),
            PriceListStatus::from($value->status()),
            PriceListScope::from($value->scope()),
            $value->validFrom(),
            $value->validTo(),
            PriceListPriority::fromInt($value->priority()),
            $value->createdAt(),
            ActorId::fromString($value->createdBy(), $this->uuids),
            $value->version(),
        );
    }
}
