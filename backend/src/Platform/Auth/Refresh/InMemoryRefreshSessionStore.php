<?php

declare(strict_types=1);

namespace Zandu\Platform\Auth\Refresh;

use Zandu\SharedKernel\Identity\SessionId;

final class InMemoryRefreshSessionStore implements RefreshSessionStore
{
    /** @var array<string,RefreshSession> */
    private array $sessions = [];

    public function add(RefreshSession $session): void
    {
        $this->sessions[$session->id()->toString()] = $session;
    }

    public function getForUpdate(SessionId $id): ?RefreshSession
    {
        return $this->sessions[$id->toString()] ?? null;
    }

    public function save(RefreshSession $session): void
    {
        $this->sessions[$session->id()->toString()] = $session;
    }

    public function transactional(callable $operation): mixed
    {
        return $operation();
    }
}
