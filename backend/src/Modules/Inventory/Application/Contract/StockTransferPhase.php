<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application\Contract;

enum StockTransferPhase: string
{
    case TransferOut = 'TRANSFER_OUT';
    case TransferIn = 'TRANSFER_IN';
}
