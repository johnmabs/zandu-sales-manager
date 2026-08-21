<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Domain\Access;

enum AccessScopeType: string
{
    case Organization = 'ORGANIZATION';
    case SelectedStores = 'SELECTED_STORES';
}
