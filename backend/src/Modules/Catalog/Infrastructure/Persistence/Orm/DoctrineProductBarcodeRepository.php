<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Infrastructure\Persistence\Orm;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Zandu\Modules\Catalog\Domain\ProductBarcode\Barcode;
use Zandu\Modules\Catalog\Domain\ProductBarcode\ProductBarcode;
use Zandu\Modules\Catalog\Domain\ProductBarcode\ProductBarcodeNotFound;
use Zandu\Modules\Catalog\Domain\ProductBarcode\ProductBarcodeRepository;
use Zandu\Modules\Catalog\Domain\ProductBarcode\ProductBarcodeStatus;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\ProductBarcodeId;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\ProductPackagingId;
use Zandu\SharedKernel\Identity\UuidFactory;

final readonly class DoctrineProductBarcodeRepository implements ProductBarcodeRepository
{
    public function __construct(private EntityManagerInterface $em, private UuidFactory $uuids) {}
    public function save(ProductBarcode $barcode): void
    {
        $r = $this->em->find(ProductBarcodeRecord::class, $barcode->id()->toString());
        if ($r instanceof ProductBarcodeRecord) {
            $e = $barcode->version() - 1;
            if ($r->version() !== $e) {
                throw OptimisticLockException::lockFailedVersionMismatch($r, $e, $r->version());
            }$r->synchronize($barcode);
        } else {
            $this->em->persist(ProductBarcodeRecord::fromAggregate($barcode));
        }$this->em->flush();
    }
    public function findByBarcode(OrganizationId $organizationId, Barcode $barcode): ?ProductBarcode
    {
        $r = $this->em->getRepository(ProductBarcodeRecord::class)->findOneBy(['organizationId' => $organizationId->toString(),'normalizedBarcode' => $barcode->normalized()]);
        if (!$r instanceof ProductBarcodeRecord) {
            return null;
        } return $this->toAggregate($r);
    }
    public function get(OrganizationId $organizationId, ProductBarcodeId $id): ProductBarcode
    {
        $r = $this->em->getRepository(ProductBarcodeRecord::class)->findOneBy(['id' => $id->toString(), 'organizationId' => $organizationId->toString()]);
        if (!$r instanceof ProductBarcodeRecord) {
            throw ProductBarcodeNotFound::withId($id);
        }
        return $this->toAggregate($r);
    }
    public function findAllByPackaging(OrganizationId $organizationId, ProductPackagingId $packagingId): array
    {
        $records = $this->em->getRepository(ProductBarcodeRecord::class)->findBy(
            ['organizationId' => $organizationId->toString(), 'packagingId' => $packagingId->toString()],
            ['createdAt' => 'ASC'],
        );
        return array_map($this->toAggregate(...), $records);
    }
    private function toAggregate(ProductBarcodeRecord $r): ProductBarcode
    {
        return ProductBarcode::reconstitute(ProductBarcodeId::fromString($r->id(), $this->uuids), OrganizationId::fromString($r->organizationId(), $this->uuids), ProductId::fromString($r->productId(), $this->uuids), ProductPackagingId::fromString($r->packagingId(), $this->uuids), Barcode::fromString($r->rawBarcode()), ProductBarcodeStatus::from($r->status()), $r->createdAt(), ActorId::fromString($r->createdBy(), $this->uuids), $r->removedAt(), null !== $r->removedBy() ? ActorId::fromString($r->removedBy(), $this->uuids) : null, $r->version());
    }
}
