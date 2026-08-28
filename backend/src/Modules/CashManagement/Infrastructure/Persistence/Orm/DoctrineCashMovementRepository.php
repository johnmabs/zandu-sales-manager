<?php

declare(strict_types=1);

namespace Zandu\Modules\CashManagement\Infrastructure\Persistence\Orm;

use Doctrine\ORM\EntityManagerInterface;
use Zandu\Modules\CashManagement\Domain\CashMovement\{CashMovement,CashMovementRepository,CashMovementType};
use Zandu\SharedKernel\Decimal\DecimalFactory;
use Zandu\SharedKernel\Identity\{ActorId,CashMovementId,CashSessionId,OrganizationId,StoreId,UuidFactory};
use Zandu\SharedKernel\Money\{Currency,Money};

final readonly class DoctrineCashMovementRepository implements CashMovementRepository
{
    public function __construct(private EntityManagerInterface $em, private UuidFactory $uuids, private DecimalFactory $decimals) {}public function append(CashMovement $m): void
    {
        $this->em->persist(CashMovementRecord::fromAggregate($m));
        $this->em->flush();
    }
    public function appendOnce(CashMovement $m): bool
    {
        $sourceType = match ($m->type()) {
            CashMovementType::SalePayment => 'SALE',
            CashMovementType::Refund => 'REFUND',
            default => 'MANUAL',
        };
        $affected = $this->em->getConnection()->executeStatement(
            'INSERT INTO cash_management.cash_movement (id, organization_id, store_id, cash_session_id, type, amount, currency, source_type, source_reference_id, reason, occurred_at, performed_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON CONFLICT (organization_id, source_type, source_reference_id) WHERE source_reference_id IS NOT NULL DO NOTHING',
            [$m->id()->toString(), $m->organizationId()->toString(), $m->storeId()->toString(), $m->sessionId()->toString(), $m->type()->value, $m->amount()->amount()->toString(), $m->amount()->currency()->code(), $sourceType, $m->sourceReference(), $m->reason(), $m->occurredAt()->format(DATE_ATOM), $m->performedBy()->toString()],
        );

        return 1 === $affected;
    }
    /** @return list<CashMovement> */
    public function findBySession(OrganizationId $o, StoreId $store, CashSessionId $s): array
    {
        return array_map(fn($r) => $this->aggregate($r), $this->em->getRepository(CashMovementRecord::class)->findBy(['organizationId' => $o->toString(),'storeId' => $store->toString(),'cashSessionId' => $s->toString()], ['occurredAt' => 'ASC']));
    }private function aggregate(CashMovementRecord $r): CashMovement
    {
        $f = $this->uuids;
        $m = Money::fromString($r->amount(), Currency::fromCode($r->currency()), $this->decimals);
        return CashMovement::record(CashMovementId::fromString($r->id(), $f), OrganizationId::fromString($r->organizationId(), $f), StoreId::fromString($r->storeId(), $f), CashSessionId::fromString($r->cashSessionId(), $f), CashMovementType::from($r->type()), $m, $r->sourceReferenceId(), $r->reason(), ActorId::fromString($r->performedBy(), $f), null, $r->occurredAt());
    }
}
