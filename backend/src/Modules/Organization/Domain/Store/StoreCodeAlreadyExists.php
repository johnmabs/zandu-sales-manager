<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Domain\Store;

use LogicException;
use Zandu\SharedKernel\Error\ResourceConflict;

final class StoreCodeAlreadyExists extends LogicException implements ResourceConflict
{
    public static function withCode(StoreCode $code): self
    {
        return new self(sprintf('A store with code "%s" already exists in this organization.', $code->value()));
    }
}
