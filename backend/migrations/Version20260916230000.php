<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260916230000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fence concurrent outbox workers with claim tokens';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE messaging.outbox_messages ADD claim_id UUID DEFAULT NULL');
        $this->addSql('CREATE INDEX outbox_expired_claim_idx ON messaging.outbox_messages (claimed_until, occurred_at) WHERE status = \'PROCESSING\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX messaging.outbox_expired_claim_idx');
        $this->addSql('ALTER TABLE messaging.outbox_messages DROP claim_id');
    }
}
