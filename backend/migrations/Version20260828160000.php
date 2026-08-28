<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260828160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Persist immutable return line amounts allocated from original sale snapshots';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
CREATE TABLE sales.return_sale_line_amount (
    return_sale_line_id UUID PRIMARY KEY,
    organization_id UUID NOT NULL,
    discount_amount NUMERIC(30,12) NOT NULL,
    taxable_amount NUMERIC(30,12) NOT NULL,
    tax_amount NUMERIC(30,12) NOT NULL,
    subtotal NUMERIC(30,12) NOT NULL,
    total NUMERIC(30,12) NOT NULL,
    currency VARCHAR(3) NOT NULL,
    CONSTRAINT return_sale_line_amount_tenant_id_unique UNIQUE (organization_id, return_sale_line_id),
    CONSTRAINT return_sale_line_amount_line_fk FOREIGN KEY (organization_id, return_sale_line_id)
        REFERENCES sales.return_sale_line (organization_id, id) ON DELETE RESTRICT,
    CONSTRAINT return_sale_line_amount_values_check CHECK (
        discount_amount >= 0 AND taxable_amount >= 0 AND tax_amount >= 0 AND subtotal >= 0 AND total >= 0
    )
)
SQL);
        $this->addSql('GRANT SELECT, INSERT ON sales.return_sale_line_amount TO zandu_runtime');
        $policy = "organization_id = NULLIF(current_setting('app.organization_id', true), '')::uuid";
        $this->addSql('ALTER TABLE sales.return_sale_line_amount ENABLE ROW LEVEL SECURITY');
        $this->addSql('ALTER TABLE sales.return_sale_line_amount FORCE ROW LEVEL SECURITY');
        $this->addSql("CREATE POLICY return_sale_line_amount_tenant_isolation ON sales.return_sale_line_amount FOR ALL TO zandu_runtime USING ($policy) WITH CHECK ($policy)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE sales.return_sale_line_amount');
    }
}
