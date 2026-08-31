<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260831160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Persist tenant-owned stock count drafts and requested scope';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
CREATE TABLE inventory.stock_count (
 id UUID NOT NULL, organization_id UUID NOT NULL, store_id UUID NOT NULL,
 status VARCHAR(16) NOT NULL, mode VARCHAR(8) NOT NULL, scope_type VARCHAR(8) NOT NULL,
 requested_product_ids UUID[] NOT NULL DEFAULT '{}',
 total_line_count INT NOT NULL DEFAULT 0, counted_line_count INT NOT NULL DEFAULT 0, reconciled_line_count INT NOT NULL DEFAULT 0,
 created_by UUID NOT NULL, created_at TIMESTAMPTZ NOT NULL,
 started_by UUID NULL, started_at TIMESTAMPTZ NULL,
 finalization_started_by UUID NULL, finalization_started_at TIMESTAMPTZ NULL,
 completed_by UUID NULL, completed_at TIMESTAMPTZ NULL,
 cancelled_by UUID NULL, cancelled_at TIMESTAMPTZ NULL,
 version INT NOT NULL,
 PRIMARY KEY (id),
 CONSTRAINT stock_count_tenant_unique UNIQUE (organization_id,id),
 CONSTRAINT stock_count_store_fk FOREIGN KEY (organization_id,store_id) REFERENCES organization.stores (organization_id,id),
 CONSTRAINT stock_count_status_check CHECK (status IN ('DRAFT','OPEN','FINALIZING','COMPLETED','CANCELLED')),
 CONSTRAINT stock_count_mode_check CHECK (mode IN ('BLIND','GUIDED')),
 CONSTRAINT stock_count_scope_type_check CHECK (scope_type IN ('FULL','PARTIAL')),
 CONSTRAINT stock_count_requested_scope_check CHECK ((scope_type='FULL' AND cardinality(requested_product_ids)=0) OR (scope_type='PARTIAL' AND cardinality(requested_product_ids)>0)),
 CONSTRAINT stock_count_progress_check CHECK (total_line_count>=0 AND counted_line_count>=0 AND reconciled_line_count>=0 AND counted_line_count<=total_line_count AND reconciled_line_count<=total_line_count),
 CONSTRAINT stock_count_version_check CHECK (version>0)
)
SQL);
        $this->addSql('CREATE INDEX stock_count_tenant_status_idx ON inventory.stock_count (organization_id,status,created_at,id)');
        $this->addSql('CREATE INDEX stock_count_store_idx ON inventory.stock_count (organization_id,store_id,status,id)');
        $this->addSql('GRANT SELECT,INSERT,UPDATE,DELETE ON inventory.stock_count TO zandu_runtime');
        $this->addSql('ALTER TABLE inventory.stock_count ENABLE ROW LEVEL SECURITY');
        $this->addSql('ALTER TABLE inventory.stock_count FORCE ROW LEVEL SECURITY');
        $this->addSql("CREATE POLICY stock_count_tenant_isolation ON inventory.stock_count FOR ALL TO zandu_runtime USING (organization_id=NULLIF(current_setting('app.organization_id',true),'')::uuid) WITH CHECK (organization_id=NULLIF(current_setting('app.organization_id',true),'')::uuid)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE inventory.stock_count');
    }
}
