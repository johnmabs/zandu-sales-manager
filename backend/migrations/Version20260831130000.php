<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260831130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Persist stock transfer reception audit fields';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE inventory.stock_transfer ADD received_by UUID NULL, ADD received_at TIMESTAMPTZ NULL');
        $this->addSql("ALTER TABLE inventory.stock_transfer ADD CONSTRAINT stock_transfer_received_audit_check CHECK ((status = 'RECEIVED' AND received_by IS NOT NULL AND received_at IS NOT NULL) OR (status <> 'RECEIVED' AND received_by IS NULL AND received_at IS NULL))");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE inventory.stock_transfer DROP CONSTRAINT stock_transfer_received_audit_check');
        $this->addSql('ALTER TABLE inventory.stock_transfer DROP received_by, DROP received_at');
    }
}
