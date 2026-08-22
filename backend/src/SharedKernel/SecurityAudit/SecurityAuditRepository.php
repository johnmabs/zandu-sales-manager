<?php

declare(strict_types=1);

namespace Zandu\SharedKernel\SecurityAudit;

interface SecurityAuditRepository
{
    public function append(SecurityAuditEntry $entry): void;
}
