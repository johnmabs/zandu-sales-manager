<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260822090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the Organization aggregate persistence schema';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA IF NOT EXISTS organization');
        $this->addSql(<<<'SQL'
CREATE TABLE organization.organizations (
    id UUID NOT NULL,
    name VARCHAR(160) NOT NULL,
    status VARCHAR(32) NOT NULL,
    country_code VARCHAR(2) NOT NULL,
    default_currency VARCHAR(3) NOT NULL,
    default_time_zone VARCHAR(64) NOT NULL,
    default_locale VARCHAR(16) NOT NULL,
    created_by UUID NOT NULL,
    created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
    updated_by UUID NOT NULL,
    updated_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
    suspended_by UUID DEFAULT NULL,
    suspended_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL,
    closure_requested_by UUID DEFAULT NULL,
    closure_requested_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL,
    closed_by UUID DEFAULT NULL,
    closed_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL,
    version INT NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT organization_status_check CHECK (status IN ('ACTIVE', 'SUSPENDED', 'CLOSURE_PENDING', 'CLOSED')),
    CONSTRAINT organization_version_check CHECK (version > 0)
)
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE organization.organizations');
        $this->addSql('DROP SCHEMA IF EXISTS organization');
    }
}
