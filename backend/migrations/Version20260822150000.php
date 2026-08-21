<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260822150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Persist the tenant-scoped store closure process manager';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
CREATE TABLE organization.store_closures (
    id UUID NOT NULL,
    organization_id UUID NOT NULL,
    store_id UUID NOT NULL,
    status VARCHAR(32) NOT NULL,
    reason VARCHAR(1000) NOT NULL,
    blockers JSON NOT NULL,
    requested_by UUID NOT NULL,
    requested_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
    completed_by UUID DEFAULT NULL,
    completed_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL,
    version INT NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT store_closure_organization_fk FOREIGN KEY (organization_id) REFERENCES organization.organizations (id),
    CONSTRAINT store_closure_store_fk FOREIGN KEY (store_id) REFERENCES organization.stores (id),
    CONSTRAINT store_closure_status_check CHECK (status IN ('REQUESTED', 'IN_PROGRESS', 'READY', 'COMPLETED', 'CANCELLED')),
    CONSTRAINT store_closure_version_check CHECK (version > 0)
)
SQL);
        $this->addSql('CREATE INDEX store_closure_tenant_store_idx ON organization.store_closures (organization_id, store_id)');
        $this->addSql("CREATE UNIQUE INDEX store_closure_one_active_idx ON organization.store_closures (organization_id, store_id) WHERE status IN ('REQUESTED', 'IN_PROGRESS', 'READY')");
        $this->addSql('GRANT SELECT, INSERT, UPDATE, DELETE ON organization.store_closures TO zandu_runtime');
        $this->addSql('ALTER TABLE organization.store_closures ENABLE ROW LEVEL SECURITY');
        $this->addSql('ALTER TABLE organization.store_closures FORCE ROW LEVEL SECURITY');
        $this->addSql(<<<'SQL'
CREATE POLICY store_closure_tenant_isolation ON organization.store_closures
    FOR ALL TO zandu_runtime
    USING (organization_id = NULLIF(current_setting('app.organization_id', true), '')::uuid)
    WITH CHECK (organization_id = NULLIF(current_setting('app.organization_id', true), '')::uuid)
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE organization.store_closures');
    }
}
