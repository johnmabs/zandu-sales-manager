<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Application;

use LogicException;
use Zandu\Modules\Sales\Application\Contract\{RefundableReturn, RefundableReturnProvider};
use Zandu\Modules\Sales\Domain\{ReturnSaleRepository, ReturnSaleStatus, SalesRuleViolation};
use Zandu\SharedKernel\Identity\{OrganizationId, ReturnSaleId};

final readonly class RepositoryRefundableReturnProvider implements RefundableReturnProvider
{
    public function __construct(private ReturnSaleRepository $returns) {}

    public function provide(OrganizationId $organizationId, ReturnSaleId $returnSaleId): RefundableReturn
    {
        $return = $this->returns->get($organizationId, $returnSaleId);
        if (ReturnSaleStatus::Completed !== $return->status()) {
            throw SalesRuleViolation::with('RETURN_NOT_REFUNDABLE', 'Only a completed return can be refunded.');
        }

        $total = null;
        foreach ($return->lines() as $line) {
            $amounts = $line->amounts() ?? throw new LogicException('Completed return line has no monetary snapshot.');
            $total = null === $total ? $amounts->total() : $total->add($amounts->total());
        }
        if (null === $total || $total->amount()->isZero()) {
            throw SalesRuleViolation::with('RETURN_AMOUNT_NOT_REFUNDABLE', 'The return has no refundable amount.');
        }

        return new RefundableReturn(
            $return->organizationId(),
            $return->storeId(),
            $return->saleId(),
            $return->id(),
            $total,
        );
    }
}
