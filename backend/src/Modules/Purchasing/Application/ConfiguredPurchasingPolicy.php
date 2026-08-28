<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Application;

use InvalidArgumentException;
use ValueError;

final readonly class ConfiguredPurchasingPolicy implements PurchasingPolicy
{
    private OverReceiptPolicy $configuredOverReceiptPolicy;

    public function __construct(
        private bool $purchaseOrderRequiredForReceipt,
        string $overReceiptPolicy,
    ) {
        try {
            $this->configuredOverReceiptPolicy = OverReceiptPolicy::from(strtoupper(trim($overReceiptPolicy)));
        } catch (ValueError $exception) {
            throw new InvalidArgumentException(
                'Unsupported over receipt policy. Configure an explicit Purchasing policy.',
                previous: $exception,
            );
        }
    }

    public function purchaseOrderRequiredForReceipt(): bool
    {
        return $this->purchaseOrderRequiredForReceipt;
    }

    public function allowsDirectReceipt(): bool
    {
        return !$this->purchaseOrderRequiredForReceipt;
    }

    public function overReceiptPolicy(): OverReceiptPolicy
    {
        return $this->configuredOverReceiptPolicy;
    }
}
