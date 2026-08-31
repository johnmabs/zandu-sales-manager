<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260831150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Persist idempotent stock transfer commands by phase';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE inventory.stock_transfer_command (organization_id UUID NOT NULL, stock_transfer_id UUID NOT NULL, phase VARCHAR(16) NOT NULL, command_id VARCHAR(128) NOT NULL, payload_hash CHAR(64) NOT NULL, completed_at TIMESTAMPTZ NOT NULL, PRIMARY KEY (organization_id,stock_transfer_id,phase,command_id), CONSTRAINT stock_transfer_command_transfer_fk FOREIGN KEY (organization_id,stock_transfer_id) REFERENCES inventory.stock_transfer (organization_id,id) ON DELETE RESTRICT, CONSTRAINT stock_transfer_command_phase_check CHECK (phase IN ('TRANSFER_OUT','TRANSFER_IN')), CONSTRAINT stock_transfer_command_id_check CHECK (command_id <> ''), CONSTRAINT stock_transfer_command_hash_check CHECK (payload_hash ~ '^[0-9a-f]{64}$'))");
        $this->addSql('GRANT SELECT,INSERT ON inventory.stock_transfer_command TO zandu_runtime');
        $this->addSql('ALTER TABLE inventory.stock_transfer_command ENABLE ROW LEVEL SECURITY');
        $this->addSql('ALTER TABLE inventory.stock_transfer_command FORCE ROW LEVEL SECURITY');
        $this->addSql("CREATE POLICY stock_transfer_command_tenant_isolation ON inventory.stock_transfer_command FOR ALL TO zandu_runtime USING (organization_id=NULLIF(current_setting('app.organization_id',true),'')::uuid) WITH CHECK (organization_id=NULLIF(current_setting('app.organization_id',true),'')::uuid)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE inventory.stock_transfer_command');
    }
}
