<?php

declare(strict_types=1);

namespace Zandu\Platform\Persistence;

use Doctrine\DBAL\Connection;
use LogicException;
use Throwable;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Tenancy\TenantTransaction;

final readonly class DoctrineTenantTransaction implements TenantTransaction
{
    public function __construct(
        private Connection $connection,
        private string $runtimeRole,
    ) {
        if (1 !== preg_match('/^[a-z_][a-z0-9_]*$/', $runtimeRole)) {
            throw new LogicException('The PostgreSQL runtime role name is invalid.');
        }
    }

    public function transactional(OrganizationId $organizationId, callable $operation): mixed
    {
        if ($this->connection->isTransactionActive()) {
            throw new LogicException('A tenant transaction cannot be nested.');
        }

        $this->connection->beginTransaction();

        try {
            $quotedRole = $this->connection->getDatabasePlatform()->quoteSingleIdentifier($this->runtimeRole);
            $this->connection->executeStatement('SET LOCAL ROLE ' . $quotedRole);
            $this->connection->fetchOne(
                "SELECT set_config('app.organization_id', ?, true)",
                [$organizationId->toString()],
            );
            $result = $operation();
            $this->connection->commit();

            return $result;
        } catch (Throwable $exception) {
            $this->connection->rollBack();

            throw $exception;
        }
    }
}
