<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Application;

enum OperationalMode
{
    case Standard;
    case Remediation;
    case Termination;
}
