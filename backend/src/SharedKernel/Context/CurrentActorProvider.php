<?php

declare(strict_types=1);

namespace Zandu\SharedKernel\Context;

interface CurrentActorProvider
{
    public function resolve(): ActorContext;
}
