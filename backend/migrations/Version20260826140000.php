<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260826140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create tenant-scoped cash sale payments';
    }
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA IF NOT EXISTS payments');
        $this->addSql("CREATE TABLE payments.payment (id UUID PRIMARY KEY, organization_id UUID NOT NULL, purpose VARCHAR(16) NOT NULL, target_reference UUID NOT NULL, method VARCHAR(16) NOT NULL, status VARCHAR(16) NOT NULL, amount NUMERIC(30,12) NOT NULL, currency VARCHAR(3) NOT NULL, created_by UUID NOT NULL, created_at TIMESTAMPTZ NOT NULL, confirmed_at TIMESTAMPTZ NULL, version INT NOT NULL DEFAULT 1, CONSTRAINT payment_tenant_id_unique UNIQUE (organization_id,id), CONSTRAINT payment_purpose_check CHECK (purpose = 'SALE'), CONSTRAINT payment_method_check CHECK (method = 'CASH'), CONSTRAINT payment_status_check CHECK (status IN ('CREATED','CONFIRMED','CANCELLED')), CONSTRAINT payment_amount_check CHECK (amount > 0), CONSTRAINT payment_version_check CHECK (version > 0))");
        $this->addSql('CREATE UNIQUE INDEX payment_sale_cash_unique ON payments.payment (organization_id,target_reference,method) WHERE status <> \'CANCELLED\'');
        $this->addSql('CREATE INDEX payment_tenant_status_idx ON payments.payment (organization_id,status,created_at)');
        $this->addSql('GRANT USAGE ON SCHEMA payments TO zandu_runtime');
        $this->addSql('GRANT SELECT, INSERT, UPDATE, DELETE ON payments.payment TO zandu_runtime');
        $this->addSql('ALTER TABLE payments.payment ENABLE ROW LEVEL SECURITY');
        $this->addSql('ALTER TABLE payments.payment FORCE ROW LEVEL SECURITY');
        $policy = "organization_id = NULLIF(current_setting('app.organization_id', true), '')::uuid";
        $this->addSql("CREATE POLICY payment_tenant_isolation ON payments.payment FOR ALL TO zandu_runtime USING ($policy) WITH CHECK ($policy)");
    }
    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS payments.payment');
    }
}
