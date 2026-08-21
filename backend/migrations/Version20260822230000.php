<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260822230000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Replace intended membership role codes with typed role assignments';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
UPDATE identity_access.organization_memberships AS membership
SET role_assignments = (
    SELECT jsonb_agg(jsonb_build_object(
        'roleId', CASE assignment->>'roleCode'
            WHEN 'ORGANIZATION_OWNER' THEN '00000000-0000-7000-8000-000000000001'
            WHEN 'STORE_MANAGER' THEN '00000000-0000-7000-8000-000000000002'
            WHEN 'CASHIER' THEN '00000000-0000-7000-8000-000000000003'
            WHEN 'ACCOUNTANT' THEN '00000000-0000-7000-8000-000000000004'
        END,
        'scopeType', CASE WHEN jsonb_array_length(assignment->'storeIds') = 0 THEN 'ORGANIZATION' ELSE 'SELECTED_STORES' END,
        'storeIds', assignment->'storeIds',
        'assignedBy', membership.created_by,
        'assignedAt', to_char(membership.created_at, 'YYYY-MM-DD"T"HH24:MI:SSOF'),
        'expiresAt', NULL
    ))
    FROM jsonb_array_elements(membership.role_assignments::jsonb) AS assignment
)::json
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
UPDATE identity_access.organization_memberships AS membership
SET role_assignments = (
    SELECT jsonb_agg(jsonb_build_object(
        'roleCode', CASE assignment->>'roleId'
            WHEN '00000000-0000-7000-8000-000000000001' THEN 'ORGANIZATION_OWNER'
            WHEN '00000000-0000-7000-8000-000000000002' THEN 'STORE_MANAGER'
            WHEN '00000000-0000-7000-8000-000000000003' THEN 'CASHIER'
            WHEN '00000000-0000-7000-8000-000000000004' THEN 'ACCOUNTANT'
        END,
        'storeIds', assignment->'storeIds'
    ))
    FROM jsonb_array_elements(membership.role_assignments::jsonb) AS assignment
)::json
SQL);
    }
}
