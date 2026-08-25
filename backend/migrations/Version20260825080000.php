<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260825080000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Persist tenant-owned units of measure with constraints and PostgreSQL RLS';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
CREATE TABLE catalog.units_of_measure (
    id UUID NOT NULL,
    organization_id UUID NOT NULL,
    code VARCHAR(32) NOT NULL,
    name VARCHAR(100) NOT NULL,
    dimension VARCHAR(16) NOT NULL,
    precision INT NOT NULL,
    rounding_mode VARCHAR(16) NOT NULL,
    status VARCHAR(16) NOT NULL,
    version INT DEFAULT 1 NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT unit_of_measure_organization_fk FOREIGN KEY (organization_id)
        REFERENCES organization.organizations (id),
    CONSTRAINT unit_of_measure_code_tenant_unique UNIQUE (organization_id, code),
    CONSTRAINT unit_of_measure_dimension_check CHECK (dimension IN ('COUNT', 'MASS', 'VOLUME', 'LENGTH', 'TIME', 'OTHER')),
    CONSTRAINT unit_of_measure_precision_check CHECK (precision BETWEEN 0 AND 12),
    CONSTRAINT unit_of_measure_rounding_mode_check CHECK (rounding_mode IN ('Unnecessary', 'Up', 'Down', 'Ceiling', 'Floor', 'HalfUp', 'HalfDown', 'HalfEven')),
    CONSTRAINT unit_of_measure_status_check CHECK (status IN ('ACTIVE', 'INACTIVE')),
    CONSTRAINT unit_of_measure_version_check CHECK (version > 0)
)
SQL);
        $this->addSql('CREATE INDEX unit_of_measure_tenant_idx ON catalog.units_of_measure (organization_id)');
        $this->addSql('GRANT USAGE ON SCHEMA catalog TO zandu_runtime');
        $this->addSql('GRANT SELECT, INSERT, UPDATE, DELETE ON catalog.units_of_measure TO zandu_runtime');
        $this->addSql('ALTER TABLE catalog.units_of_measure ENABLE ROW LEVEL SECURITY');
        $this->addSql('ALTER TABLE catalog.units_of_measure FORCE ROW LEVEL SECURITY');
        $this->addSql(<<<'SQL'
CREATE POLICY unit_of_measure_tenant_isolation ON catalog.units_of_measure
    FOR ALL
    TO zandu_runtime
    USING (organization_id = NULLIF(current_setting('app.organization_id', true), '')::uuid)
    WITH CHECK (organization_id = NULLIF(current_setting('app.organization_id', true), '')::uuid)
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE catalog.units_of_measure');
    }
}
