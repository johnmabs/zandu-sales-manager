<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260826150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Persist tenant-scoped CompleteSale idempotency keys';
    }
    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE sales.sale_completion_keys (organization_id UUID NOT NULL, sale_id UUID NOT NULL, idempotency_key VARCHAR(128) NOT NULL, completed_at TIMESTAMPTZ NOT NULL, PRIMARY KEY (organization_id, sale_id, idempotency_key), CONSTRAINT sale_completion_key_sale_fk FOREIGN KEY (organization_id,sale_id) REFERENCES sales.sale(organization_id,id) ON DELETE RESTRICT)");
        $this->addSql('GRANT SELECT, INSERT ON sales.sale_completion_keys TO zandu_runtime');
        $this->addSql('ALTER TABLE sales.sale_completion_keys ENABLE ROW LEVEL SECURITY');
        $this->addSql('ALTER TABLE sales.sale_completion_keys FORCE ROW LEVEL SECURITY');
        $policy = "organization_id = NULLIF(current_setting('app.organization_id', true), '')::uuid";
        $this->addSql("CREATE POLICY sale_completion_key_tenant_isolation ON sales.sale_completion_keys FOR ALL TO zandu_runtime USING ($policy) WITH CHECK ($policy)");
    }
    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS sales.sale_completion_keys');
    }
}
