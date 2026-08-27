<?php

declare(strict_types=1);

namespace Zandu\Modules\InventoryCosting\Application\InitializeStockValuation;

use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Inventory\Application\Contract\CostingStockPositionProvider;
use Zandu\Modules\InventoryCosting\Domain\InventoryCostingRuleViolation;
use Zandu\Modules\InventoryCosting\Domain\Valuation\{MovingWeightedAverageCalculator, StockValuation, StockValuationRepository};
use Zandu\Modules\InventoryCosting\Domain\ValuationMovement\{StockValuationMovement, StockValuationMovementRepository, StockValuationMovementSource, StockValuationMovementType};
use Zandu\Modules\Organization\Application\Contract\{OperationalGuard, OperationalMode, StoreBusinessContextProvider};
use Zandu\SharedKernel\Access\{PermissionCode, ResourceScope};
use Zandu\SharedKernel\Decimal\RoundingMode;
use Zandu\SharedKernel\Identity\{IdGenerator, StockValuationId, StockValuationMovementId};
use Zandu\SharedKernel\Money\{Currency, Money};
use Zandu\SharedKernel\SecurityAudit\{ResourceReference, SafeAuditMetadata, SecurityAction, SecurityAuditTrail};
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class InitializeStockValuationHandler
{
    public function __construct(
        private CostingStockPositionProvider $stockPositions,
        private StockValuationRepository $valuations,
        private StockValuationMovementRepository $movements,
        private StoreBusinessContextProvider $stores,
        private MovingWeightedAverageCalculator $calculator,
        private IdGenerator $ids,
        private Clock $clock,
        private TenantTransaction $transaction,
        private OperationalGuard $operationalGuard,
        private AuthorizationService $authorization,
        private SecurityAuditTrail $audit,
    ) {}

    public function __invoke(InitializeStockValuation $command): StockValuation
    {
        $organizationId = $command->actorContext->organizationId();

        return $this->transaction->transactional($organizationId, function () use ($command, $organizationId): StockValuation {
            $this->authorization->authorize(
                $command->actorContext,
                PermissionCode::InventoryCostingInitialize,
                ResourceScope::store($organizationId, $command->storeId),
            );
            $this->operationalGuard->assertStore($command->actorContext, $command->storeId, OperationalMode::Standard);

            $position = $this->stockPositions->getForUpdate($organizationId, $command->storeId, $command->productId);
            if (null !== $this->valuations->findByStock($organizationId, $position->stockId)) {
                throw InventoryCostingRuleViolation::with(
                    'VALUATION_ALREADY_INITIALIZED',
                    'Stock valuation is already initialized.',
                );
            }

            $currency = Currency::fromCode($this->stores->provide($organizationId, $command->storeId)->currency);
            $openingUnitCost = (new Money($command->openingUnitCost, $currency))->withScale(12, RoundingMode::HalfEven);
            if ($position->quantityOnHand->isZero() && !$openingUnitCost->amount()->isZero()) {
                throw InventoryCostingRuleViolation::with(
                    'VALUATION_ZERO_QUANTITY_COST_INVALID',
                    'A zero stock position must be initialized with a zero unit cost.',
                );
            }

            $valuation = StockValuation::initialize(
                StockValuationId::generate($this->ids),
                $organizationId,
                $position->storeId,
                $position->productId,
                $position->stockId,
                $position->quantityOnHand,
                $openingUnitCost,
                $this->calculator,
            );
            $now = $this->clock->now();
            $zeroValue = $openingUnitCost->subtract($openingUnitCost)->withScale(6, RoundingMode::HalfEven);
            $zeroAverage = $openingUnitCost->subtract($openingUnitCost)->withScale(12, RoundingMode::HalfEven);
            $movement = StockValuationMovement::record(
                StockValuationMovementId::generate($this->ids),
                $valuation->id(),
                $organizationId,
                $position->storeId,
                $position->productId,
                $position->stockId,
                null,
                StockValuationMovementType::Opening,
                $position->quantityOnHand,
                $openingUnitCost,
                $valuation->totalValue(),
                $zeroValue,
                $valuation->totalValue(),
                $zeroAverage,
                $valuation->averageUnitCost(),
                StockValuationMovementSource::from(StockValuationMovementType::Opening->sourceType(), $command->reason),
                $now,
                $command->actorContext->correlationId(),
            );

            $this->valuations->save($valuation);
            $this->movements->append($movement);
            $this->audit->recordSuccess(
                $command->actorContext,
                SecurityAction::StockValuationInitialized,
                ResourceReference::for('stock_valuation', $valuation->id()),
                SafeAuditMetadata::fromArray([
                    'storeId' => $position->storeId->toString(),
                    'productId' => $position->productId->toString(),
                    'reason' => $command->reason,
                ]),
                $now,
            );

            return $valuation;
        });
    }
}
