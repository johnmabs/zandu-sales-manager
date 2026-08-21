<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Domain\User;

interface UserRepository
{
    public function save(User $user): void;
    public function findByEmail(UserEmail $email): ?User;
}
