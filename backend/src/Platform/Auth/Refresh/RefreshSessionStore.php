<?php

declare(strict_types=1);

namespace Zandu\Platform\Auth\Refresh;

use Zandu\SharedKernel\Identity\SessionId;

interface RefreshSessionStore
{
    public function add(RefreshSession $session): void;

    public function getForUpdate(SessionId $id): ?RefreshSession;

    public function save(RefreshSession $session): void;

    public function transactional(callable $operation): mixed;
}
