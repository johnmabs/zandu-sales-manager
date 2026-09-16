<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260916233000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename the terminal outbox status from DEAD_LETTER to FAILED';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE messaging.outbox_messages DROP CONSTRAINT outbox_status_check');
        $this->addSql("UPDATE messaging.outbox_messages SET status = 'FAILED' WHERE status = 'DEAD_LETTER'");
        $this->addSql("ALTER TABLE messaging.outbox_messages ADD CONSTRAINT outbox_status_check CHECK (status IN ('PENDING', 'PROCESSING', 'PUBLISHED', 'FAILED'))");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE messaging.outbox_messages DROP CONSTRAINT outbox_status_check');
        $this->addSql("UPDATE messaging.outbox_messages SET status = 'DEAD_LETTER' WHERE status = 'FAILED'");
        $this->addSql("ALTER TABLE messaging.outbox_messages ADD CONSTRAINT outbox_status_check CHECK (status IN ('PENDING', 'PROCESSING', 'PUBLISHED', 'DEAD_LETTER'))");
    }
}
