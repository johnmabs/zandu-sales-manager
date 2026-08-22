<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260823090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the tenant application transactional outbox';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA IF NOT EXISTS messaging');
        $this->addSql(<<<'SQL'
CREATE TABLE messaging.outbox_messages (
    id UUID NOT NULL,
    organization_id UUID NOT NULL,
    type VARCHAR(160) NOT NULL,
    payload JSONB NOT NULL,
    correlation_id UUID NOT NULL,
    causation_id UUID DEFAULT NULL,
    occurred_at TIMESTAMPTZ NOT NULL,
    available_at TIMESTAMPTZ NOT NULL,
    status VARCHAR(16) NOT NULL DEFAULT 'PENDING',
    attempts INT NOT NULL DEFAULT 0,
    claimed_until TIMESTAMPTZ DEFAULT NULL,
    published_at TIMESTAMPTZ DEFAULT NULL,
    last_error VARCHAR(1000) DEFAULT NULL,
    PRIMARY KEY (id),
    CONSTRAINT outbox_organization_fk FOREIGN KEY (organization_id)
        REFERENCES organization.organizations (id),
    CONSTRAINT outbox_status_check CHECK (status IN ('PENDING', 'PROCESSING', 'PUBLISHED', 'DEAD_LETTER')),
    CONSTRAINT outbox_attempts_check CHECK (attempts >= 0)
)
SQL);
        $this->addSql('CREATE INDEX outbox_pending_idx ON messaging.outbox_messages (status, available_at, occurred_at)');
        $this->addSql('GRANT USAGE ON SCHEMA messaging TO zandu_runtime');
        $this->addSql('GRANT SELECT, INSERT, UPDATE ON messaging.outbox_messages TO zandu_runtime');
        $this->addSql('ALTER TABLE messaging.outbox_messages ENABLE ROW LEVEL SECURITY');
        $this->addSql('ALTER TABLE messaging.outbox_messages FORCE ROW LEVEL SECURITY');
        $this->addSql(<<<'SQL'
CREATE POLICY outbox_tenant_isolation ON messaging.outbox_messages FOR ALL TO zandu_runtime
USING (organization_id = NULLIF(current_setting('app.organization_id', true), '')::uuid)
WITH CHECK (organization_id = NULLIF(current_setting('app.organization_id', true), '')::uuid)
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE messaging.outbox_messages');
        $this->addSql('DROP SCHEMA messaging');
    }
}
