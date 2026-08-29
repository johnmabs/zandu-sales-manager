<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260829130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Persist immutable tenant-owned goods receipt corrections';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
CREATE TABLE purchasing.goods_receipt_correction (
    id UUID NOT NULL,
    organization_id UUID NOT NULL,
    goods_receipt_id UUID NOT NULL,
    reason VARCHAR(500) NOT NULL,
    status VARCHAR(16) NOT NULL,
    created_by UUID NOT NULL,
    created_at TIMESTAMPTZ NOT NULL,
    posted_by UUID NULL,
    posted_at TIMESTAMPTZ NULL,
    version INT NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT goods_receipt_correction_tenant_id_unique UNIQUE (organization_id, id),
    CONSTRAINT goods_receipt_correction_receipt_fk FOREIGN KEY (organization_id, goods_receipt_id) REFERENCES purchasing.goods_receipt (organization_id, id) ON DELETE RESTRICT,
    CONSTRAINT goods_receipt_correction_reason_check CHECK (length(btrim(reason)) BETWEEN 1 AND 500),
    CONSTRAINT goods_receipt_correction_status_check CHECK (status IN ('DRAFT', 'POSTED')),
    CONSTRAINT goods_receipt_correction_post_check CHECK ((status = 'POSTED') = (posted_at IS NOT NULL) AND (posted_by IS NULL) = (posted_at IS NULL)),
    CONSTRAINT goods_receipt_correction_version_check CHECK (version > 0)
)
SQL);
        $this->addSql(<<<'SQL'
CREATE TABLE purchasing.goods_receipt_correction_line (
    organization_id UUID NOT NULL,
    correction_id UUID NOT NULL,
    product_id UUID NOT NULL,
    original_received_quantity NUMERIC(30,12) NOT NULL,
    current_effective_quantity NUMERIC(30,12) NOT NULL,
    corrected_received_quantity NUMERIC(30,12) NOT NULL,
    difference NUMERIC(30,12) NOT NULL,
    PRIMARY KEY (organization_id, correction_id, product_id),
    CONSTRAINT goods_receipt_correction_line_correction_fk FOREIGN KEY (organization_id, correction_id) REFERENCES purchasing.goods_receipt_correction (organization_id, id) ON DELETE RESTRICT,
    CONSTRAINT goods_receipt_correction_line_product_fk FOREIGN KEY (organization_id, product_id) REFERENCES catalog.products (organization_id, id) ON DELETE RESTRICT,
    CONSTRAINT goods_receipt_correction_line_quantity_check CHECK (original_received_quantity > 0 AND current_effective_quantity >= 0 AND corrected_received_quantity >= 0),
    CONSTRAINT goods_receipt_correction_line_difference_check CHECK (difference = corrected_received_quantity - current_effective_quantity)
)
SQL);
        $this->addSql('CREATE INDEX goods_receipt_correction_receipt_idx ON purchasing.goods_receipt_correction (organization_id, goods_receipt_id, status, created_at, id)');
        $this->addSql(<<<'SQL'
CREATE FUNCTION purchasing.guard_goods_receipt_correction_line_mutation() RETURNS trigger
LANGUAGE plpgsql AS $$
DECLARE correction_status VARCHAR(16);
BEGIN
    SELECT status INTO correction_status FROM purchasing.goods_receipt_correction
    WHERE organization_id = COALESCE(NEW.organization_id, OLD.organization_id)
      AND id = COALESCE(NEW.correction_id, OLD.correction_id);
    IF correction_status <> 'DRAFT' THEN
        RAISE EXCEPTION 'Goods receipt correction lines can only be changed while the correction is DRAFT';
    END IF;
    IF TG_OP = 'DELETE' THEN RETURN OLD; END IF;
    RETURN NEW;
END;
$$
SQL);
        $this->addSql('CREATE TRIGGER goods_receipt_correction_line_mutation_guard BEFORE INSERT OR UPDATE OR DELETE ON purchasing.goods_receipt_correction_line FOR EACH ROW EXECUTE FUNCTION purchasing.guard_goods_receipt_correction_line_mutation()');
        $this->addSql('GRANT SELECT, INSERT, UPDATE ON purchasing.goods_receipt_correction TO zandu_runtime');
        $this->addSql('GRANT SELECT, INSERT, UPDATE, DELETE ON purchasing.goods_receipt_correction_line TO zandu_runtime');
        foreach (['goods_receipt_correction', 'goods_receipt_correction_line'] as $table) {
            $this->addSql(sprintf('ALTER TABLE purchasing.%s ENABLE ROW LEVEL SECURITY', $table));
            $this->addSql(sprintf('ALTER TABLE purchasing.%s FORCE ROW LEVEL SECURITY', $table));
            $this->addSql(sprintf("CREATE POLICY %s_tenant_isolation ON purchasing.%s FOR ALL TO zandu_runtime USING (organization_id = NULLIF(current_setting('app.organization_id', true), '')::uuid) WITH CHECK (organization_id = NULLIF(current_setting('app.organization_id', true), '')::uuid)", $table, $table));
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE purchasing.goods_receipt_correction_line');
        $this->addSql('DROP FUNCTION purchasing.guard_goods_receipt_correction_line_mutation()');
        $this->addSql('DROP TABLE purchasing.goods_receipt_correction');
    }
}
