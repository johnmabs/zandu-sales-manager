<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260820145013 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create initial bounded context PostgreSQL schemas';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA IF NOT EXISTS sales');
        $this->addSql('CREATE SCHEMA IF NOT EXISTS inventory');
        $this->addSql('CREATE SCHEMA IF NOT EXISTS cash_management');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP SCHEMA IF EXISTS cash_management');
        $this->addSql('DROP SCHEMA IF EXISTS inventory');
        $this->addSql('DROP SCHEMA IF EXISTS sales');
    }
}
