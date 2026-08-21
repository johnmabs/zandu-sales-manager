<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260822110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Enable fail-closed tenant RLS on organizations for the application runtime role';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'zandu_runtime') THEN
        CREATE ROLE zandu_runtime NOLOGIN NOSUPERUSER NOCREATEDB NOCREATEROLE NOINHERIT NOBYPASSRLS;
    ELSE
        ALTER ROLE zandu_runtime NOLOGIN NOSUPERUSER NOCREATEDB NOCREATEROLE NOINHERIT NOBYPASSRLS;
    END IF;

    EXECUTE format('GRANT zandu_runtime TO %I', current_user);
END
$$
SQL);
        $this->addSql('GRANT USAGE ON SCHEMA organization TO zandu_runtime');
        $this->addSql('GRANT SELECT, INSERT, UPDATE, DELETE ON organization.organizations TO zandu_runtime');
        $this->addSql('ALTER TABLE organization.organizations ENABLE ROW LEVEL SECURITY');
        $this->addSql('ALTER TABLE organization.organizations FORCE ROW LEVEL SECURITY');
        $this->addSql(<<<'SQL'
CREATE POLICY organization_tenant_isolation ON organization.organizations
    FOR ALL
    TO zandu_runtime
    USING (id = NULLIF(current_setting('app.organization_id', true), '')::uuid)
    WITH CHECK (id = NULLIF(current_setting('app.organization_id', true), '')::uuid)
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP POLICY IF EXISTS organization_tenant_isolation ON organization.organizations');
        $this->addSql('ALTER TABLE organization.organizations DISABLE ROW LEVEL SECURITY');
        $this->addSql('REVOKE ALL PRIVILEGES ON organization.organizations FROM zandu_runtime');
        $this->addSql('REVOKE USAGE ON SCHEMA organization FROM zandu_runtime');
    }
}
