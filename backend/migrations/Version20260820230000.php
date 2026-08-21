<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260820230000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create isolated PostgreSQL tables used by reproducible Lot 0 architecture spikes';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA IF NOT EXISTS architecture_spike');
        $this->addSql('CREATE TABLE architecture_spike.sale (id UUID PRIMARY KEY, status VARCHAR(20) NOT NULL)');
        $this->addSql('CREATE TABLE architecture_spike.stock (id UUID PRIMARY KEY, quantity NUMERIC(30, 12) NOT NULL, version INT NOT NULL DEFAULT 0, CHECK (quantity >= 0))');
        $this->addSql('CREATE TABLE architecture_spike.stock_movement (id UUID PRIMARY KEY, stock_id UUID NOT NULL, quantity NUMERIC(30, 12) NOT NULL)');
        $this->addSql('CREATE TABLE architecture_spike.cash_session (id UUID PRIMARY KEY, balance NUMERIC(30, 12) NOT NULL)');
        $this->addSql('CREATE TABLE architecture_spike.cash_movement (id UUID PRIMARY KEY, session_id UUID NOT NULL, amount NUMERIC(30, 12) NOT NULL)');
        $this->addSql(<<<'SQL'
CREATE TABLE architecture_spike.outbox_message (
    id UUID PRIMARY KEY,
    payload JSONB NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'PENDING',
    attempts INT NOT NULL DEFAULT 0,
    available_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
    claimed_until TIMESTAMP WITH TIME ZONE DEFAULT NULL
)
SQL);
        $this->addSql('CREATE TABLE architecture_spike.processed_message (consumer VARCHAR(100) NOT NULL, message_id UUID NOT NULL, PRIMARY KEY (consumer, message_id))');
        $this->addSql('CREATE TABLE architecture_spike.numeric_roundtrip (case_name VARCHAR(100) PRIMARY KEY, value NUMERIC(30, 12) NOT NULL)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP SCHEMA IF EXISTS architecture_spike CASCADE');
    }
}
