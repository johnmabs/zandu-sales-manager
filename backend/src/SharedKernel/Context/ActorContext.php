<?php

declare(strict_types=1);

namespace Zandu\SharedKernel\Context;

use DateTimeImmutable;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\SessionId;
use Zandu\SharedKernel\Identity\UserId;
use Zandu\SharedKernel\Messaging\CorrelationId;

final readonly class ActorContext
{
    public function __construct(
        private ActorId $actorId,
        private OrganizationId $organizationId,
        private ActorType $actorType,
        private CorrelationId $correlationId,
        private DateTimeImmutable $authenticatedAt,
        private ?UserId $userId = null,
        private ?SessionId $sessionId = null,
    ) {
    }

    public function actorId(): ActorId
    {
        return $this->actorId;
    }

    public function organizationId(): OrganizationId
    {
        return $this->organizationId;
    }

    public function actorType(): ActorType
    {
        return $this->actorType;
    }

    public function correlationId(): CorrelationId
    {
        return $this->correlationId;
    }

    public function authenticatedAt(): DateTimeImmutable
    {
        return $this->authenticatedAt;
    }

    public function userId(): ?UserId
    {
        return $this->userId;
    }

    public function sessionId(): ?SessionId
    {
        return $this->sessionId;
    }
}
