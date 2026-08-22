<?php

declare(strict_types=1);

namespace Zandu\SharedKernel\SecurityAudit;

enum AuditOutcome: string
{
    case Success = 'SUCCESS';
    case Denied = 'DENIED';
    case Failed = 'FAILED';
}
