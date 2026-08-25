<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260825230000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Persist tenant-owned price lists in the Pricing bounded context with RLS';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA IF NOT EXISTS pricing');
        $this->addSql(<<<'SQL'
CREATE TABLE pricing.price_lists (
 id UUID NOT NULL, organization_id UUID NOT NULL, code VARCHAR(64) NOT NULL,
 name VARCHAR(160) NOT NULL, currency VARCHAR(3) NOT NULL, status VARCHAR(16) NOT NULL,
 scope VARCHAR(16) NOT NULL, valid_from TIMESTAMPTZ NULL, valid_to TIMESTAMPTZ NULL,
 priority INT NOT NULL, created_at TIMESTAMPTZ NOT NULL, created_by UUID NOT NULL,
 version INT DEFAULT 1 NOT NULL, PRIMARY KEY (id),
 CONSTRAINT price_list_tenant_code_unique UNIQUE (organization_id, code),
 CONSTRAINT price_list_organization_fk FOREIGN KEY (organization_id) REFERENCES organization.organizations (id),
 CONSTRAINT price_list_currency_check CHECK (currency ~ '^[A-Z]{3}$'),
 CONSTRAINT price_list_status_check CHECK (status IN ('DRAFT','ACTIVE','INACTIVE','ARCHIVED')),
 CONSTRAINT price_list_scope_check CHECK (scope = 'ORGANIZATION'),
 CONSTRAINT price_list_period_check CHECK (valid_to IS NULL OR valid_from IS NULL OR valid_to >= valid_from),
 CONSTRAINT price_list_priority_check CHECK (priority >= 0),
 CONSTRAINT price_list_version_check CHECK (version > 0)
)
SQL);
        $this->addSql('CREATE INDEX price_list_resolution_idx ON pricing.price_lists (organization_id, status, priority DESC)');
        $this->addSql('GRANT USAGE ON SCHEMA pricing TO zandu_runtime');
        $this->addSql('GRANT SELECT, INSERT, UPDATE, DELETE ON pricing.price_lists TO zandu_runtime');
        $this->addSql('ALTER TABLE pricing.price_lists ENABLE ROW LEVEL SECURITY');
        $this->addSql('ALTER TABLE pricing.price_lists FORCE ROW LEVEL SECURITY');
        $this->addSql("CREATE POLICY price_list_tenant_isolation ON pricing.price_lists FOR ALL TO zandu_runtime USING (organization_id = NULLIF(current_setting('app.organization_id', true), '')::uuid) WITH CHECK (organization_id = NULLIF(current_setting('app.organization_id', true), '')::uuid)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE pricing.price_lists');
        $this->addSql('DROP SCHEMA pricing');
    }
}
