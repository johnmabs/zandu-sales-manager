<?php

declare(strict_types=1);

namespace Zandu\SharedKernel\Context;

enum ActorType
{
    case User;
    case ServiceAccount;
    case System;
}
