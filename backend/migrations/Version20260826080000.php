<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260826080000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Persist product prices with tenant-safe targets, exact amounts, non-overlapping periods and RLS';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE EXTENSION IF NOT EXISTS btree_gist');
        $this->addSql('ALTER TABLE pricing.price_lists ADD CONSTRAINT price_list_tenant_id_currency_unique UNIQUE (organization_id, id, currency)');
        $this->addSql(<<<'SQL'
CREATE TABLE pricing.product_prices (
 id UUID NOT NULL, organization_id UUID NOT NULL, price_list_id UUID NOT NULL,
 product_id UUID NOT NULL, packaging_id UUID NOT NULL,
 amount NUMERIC(30,12) NOT NULL, currency VARCHAR(3) NOT NULL, status VARCHAR(16) NOT NULL,
 valid_from TIMESTAMPTZ NULL, valid_to TIMESTAMPTZ NULL,
 created_at TIMESTAMPTZ NOT NULL, created_by UUID NOT NULL,
 version INT DEFAULT 1 NOT NULL, PRIMARY KEY (id),
 CONSTRAINT product_price_list_fk FOREIGN KEY (organization_id, price_list_id, currency)
   REFERENCES pricing.price_lists (organization_id, id, currency),
 CONSTRAINT product_price_packaging_fk FOREIGN KEY (organization_id, product_id, packaging_id)
   REFERENCES catalog.product_packagings (organization_id, product_id, id),
 CONSTRAINT product_price_amount_check CHECK (amount >= 0),
 CONSTRAINT product_price_currency_check CHECK (currency ~ '^[A-Z]{3}$'),
 CONSTRAINT product_price_status_check CHECK (status IN ('ACTIVE','INACTIVE','ARCHIVED')),
 CONSTRAINT product_price_period_check CHECK (valid_to IS NULL OR valid_from IS NULL OR valid_to >= valid_from),
 CONSTRAINT product_price_version_check CHECK (version > 0)
)
SQL);
        $this->addSql(<<<'SQL'
ALTER TABLE pricing.product_prices ADD CONSTRAINT product_price_active_period_exclusion
EXCLUDE USING gist (
 organization_id WITH =,
 price_list_id WITH =,
 product_id WITH =,
 packaging_id WITH =,
 tstzrange(COALESCE(valid_from, '-infinity'::timestamptz), COALESCE(valid_to, 'infinity'::timestamptz), '[]') WITH &&
) WHERE (status = 'ACTIVE')
SQL);
        $this->addSql('CREATE INDEX product_price_resolution_idx ON pricing.product_prices (organization_id, product_id, packaging_id, status, price_list_id)');
        $this->addSql('GRANT SELECT, INSERT, UPDATE, DELETE ON pricing.product_prices TO zandu_runtime');
        $this->addSql('ALTER TABLE pricing.product_prices ENABLE ROW LEVEL SECURITY');
        $this->addSql('ALTER TABLE pricing.product_prices FORCE ROW LEVEL SECURITY');
        $this->addSql("CREATE POLICY product_price_tenant_isolation ON pricing.product_prices FOR ALL TO zandu_runtime USING (organization_id = NULLIF(current_setting('app.organization_id', true), '')::uuid) WITH CHECK (organization_id = NULLIF(current_setting('app.organization_id', true), '')::uuid)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE pricing.product_prices');
        $this->addSql('ALTER TABLE pricing.price_lists DROP CONSTRAINT price_list_tenant_id_currency_unique');
    }
}
