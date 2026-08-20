<?php

declare(strict_types=1);

namespace Zandu\SharedKernel\Identity;

interface IdGenerator
{
    public function generate(): Uuid;
}
