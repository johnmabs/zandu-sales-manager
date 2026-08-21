<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Application\RegisterFromInvitation;

use SensitiveParameter;
use Zandu\SharedKernel\Messaging\CorrelationId;

final readonly class RegisterFromInvitation
{
    public function __construct(
        #[SensitiveParameter]
        public string $token,
        #[SensitiveParameter]
        public string $password,
        public CorrelationId $correlationId,
    ) {}
}
