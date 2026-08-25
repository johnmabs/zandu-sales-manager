<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260825210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Persist tenant-unique product barcodes without numeric interpretation';
    }
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE catalog.product_packagings ADD CONSTRAINT product_packaging_tenant_product_id_unique UNIQUE(organization_id,product_id,id)');
        $this->addSql("CREATE TABLE catalog.product_barcodes (id UUID NOT NULL, organization_id UUID NOT NULL, product_id UUID NOT NULL, packaging_id UUID NOT NULL, raw_barcode VARCHAR(128) NOT NULL, normalized_barcode VARCHAR(128) NOT NULL, status VARCHAR(16) NOT NULL, created_at TIMESTAMPTZ NOT NULL, created_by UUID NOT NULL, removed_at TIMESTAMPTZ NULL, removed_by UUID NULL, version INT DEFAULT 1 NOT NULL, PRIMARY KEY(id), CONSTRAINT product_barcode_tenant_unique UNIQUE(organization_id,normalized_barcode), CONSTRAINT product_barcode_product_fk FOREIGN KEY(organization_id,product_id) REFERENCES catalog.products(organization_id,id), CONSTRAINT product_barcode_packaging_fk FOREIGN KEY(organization_id,product_id,packaging_id) REFERENCES catalog.product_packagings(organization_id,product_id,id), CONSTRAINT product_barcode_status_check CHECK(status IN ('ACTIVE','REMOVED')), CONSTRAINT product_barcode_remove_audit_check CHECK((removed_at IS NULL)=(removed_by IS NULL)), CONSTRAINT product_barcode_removed_status_check CHECK(status<>'REMOVED' OR removed_at IS NOT NULL), CONSTRAINT product_barcode_version_check CHECK(version>0))");
        $this->addSql('CREATE INDEX product_barcode_packaging_idx ON catalog.product_barcodes(organization_id,packaging_id)');
        $this->addSql('GRANT SELECT,INSERT,UPDATE,DELETE ON catalog.product_barcodes TO zandu_runtime');
        $this->addSql('ALTER TABLE catalog.product_barcodes ENABLE ROW LEVEL SECURITY');
        $this->addSql('ALTER TABLE catalog.product_barcodes FORCE ROW LEVEL SECURITY');
        $this->addSql("CREATE POLICY product_barcode_tenant_isolation ON catalog.product_barcodes FOR ALL TO zandu_runtime USING (organization_id=NULLIF(current_setting('app.organization_id',true),'')::uuid) WITH CHECK (organization_id=NULLIF(current_setting('app.organization_id',true),'')::uuid)");
    }
    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE catalog.product_barcodes');
        $this->addSql('ALTER TABLE catalog.product_packagings DROP CONSTRAINT product_packaging_tenant_product_id_unique');
    }
}
