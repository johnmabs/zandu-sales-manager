<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260827092000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Bind sale completion idempotency keys to an immutable request payload';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE sales.sale_completion_keys ADD payload_hash VARCHAR(64) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE sales.sale_completion_keys DROP payload_hash');
    }
}
