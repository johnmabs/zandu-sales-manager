<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Application;

interface PurchasingPolicy
{
    public function purchaseOrderRequiredForReceipt(): bool;

    public function allowsDirectReceipt(): bool;

    public function overReceiptPolicy(): OverReceiptPolicy;
}
