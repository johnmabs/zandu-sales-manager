<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Application\CreateGoodsReceiptCorrection;

use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\Modules\Purchasing\Domain\GoodsReceipt\GoodsReceiptLine;
use Zandu\Modules\Purchasing\Domain\GoodsReceipt\GoodsReceiptRepository;
use Zandu\Modules\Purchasing\Domain\GoodsReceipt\GoodsReceiptStatus;
use Zandu\Modules\Purchasing\Domain\GoodsReceiptCorrection\GoodsReceiptCorrection;
use Zandu\Modules\Purchasing\Domain\GoodsReceiptCorrection\GoodsReceiptCorrectionLine;
use Zandu\Modules\Purchasing\Domain\GoodsReceiptCorrection\GoodsReceiptCorrectionRepository;
use Zandu\Modules\Purchasing\Domain\PurchasingRuleViolation;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Access\ResourceScope;
use Zandu\SharedKernel\Identity\GoodsReceiptCorrectionId;
use Zandu\SharedKernel\Identity\IdGenerator;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class CreateGoodsReceiptCorrectionHandler
{
    public function __construct(
        private GoodsReceiptRepository $goodsReceipts,
        private GoodsReceiptCorrectionRepository $corrections,
        private IdGenerator $ids,
        private Clock $clock,
        private TenantTransaction $transaction,
        private AuthorizationService $authorization,
        private OperationalGuard $operationalGuard,
    ) {}

    public function __invoke(CreateGoodsReceiptCorrection $command): GoodsReceiptCorrection
    {
        $organizationId = $command->actorContext->organizationId();
        return $this->transaction->transactional($organizationId, function () use ($command, $organizationId): GoodsReceiptCorrection {
            $receipt = $this->goodsReceipts->getForUpdate($organizationId, $command->goodsReceiptId);
            $scope = ResourceScope::store($organizationId, $receipt->storeId());
            $this->authorization->authorize($command->actorContext, PermissionCode::PurchasingReceiptCorrect, $scope);
            $this->operationalGuard->assertStore($command->actorContext, $receipt->storeId());
            if (GoodsReceiptStatus::Posted !== $receipt->status()) {
                throw PurchasingRuleViolation::with('GOODS_RECEIPT_CORRECTION_RECEIPT_NOT_POSTED', 'Only a posted goods receipt can be corrected.');
            }
            if ([] === $command->lines) {
                throw PurchasingRuleViolation::with('GOODS_RECEIPT_CORRECTION_EMPTY', 'A correction must contain at least one line.');
            }
            $postedDifferences = $this->corrections->postedDifferenceByProduct($organizationId, $receipt->id());
            $correction = GoodsReceiptCorrection::create(GoodsReceiptCorrectionId::generate($this->ids), $organizationId, $receipt->id(), $command->reason, $command->actorContext->actorId(), $this->clock->now());
            foreach ($command->lines as $input) {
                $original = array_find($receipt->lines(), static fn(GoodsReceiptLine $line): bool => $line->productId()->equals($input->productId));
                if (!$original instanceof GoodsReceiptLine) {
                    throw PurchasingRuleViolation::with('GOODS_RECEIPT_CORRECTION_PRODUCT_NOT_RECEIVED', 'A correction product must belong to the original receipt.');
                }
                $current = $original->receivedBaseQuantity();
                if (isset($postedDifferences[$input->productId->toString()])) {
                    $current = $current->add($postedDifferences[$input->productId->toString()]);
                }
                $correction->addLine(new GoodsReceiptCorrectionLine($correction->id(), $input->productId, $original->receivedBaseQuantity(), $current, $input->correctedReceivedQuantity));
            }
            $this->corrections->save($correction);
            return $correction;
        });
    }
}
