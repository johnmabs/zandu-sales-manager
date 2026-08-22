<?php

declare(strict_types=1);

namespace Zandu\SharedKernel\SecurityAudit;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Context\ActorType;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\UserId;

final readonly class ActorReference
{
    public function __construct(
        public ActorId $actorId,
        public ActorType $actorType,
        public ?UserId $userId,
    ) {}

    public static function fromContext(ActorContext $context): self
    {
        return new self($context->actorId(), $context->actorType(), $context->userId());
    }
}
