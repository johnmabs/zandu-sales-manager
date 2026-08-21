<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260823010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add global persistent user accounts';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
CREATE TABLE identity_access.users (
    id UUID NOT NULL,
    actor_id UUID NOT NULL,
    email VARCHAR(254) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    status VARCHAR(16) NOT NULL,
    created_at TIMESTAMPTZ NOT NULL,
    updated_at TIMESTAMPTZ NOT NULL,
    version INT NOT NULL,
    PRIMARY KEY(id)
)
SQL);
        $this->addSql('CREATE UNIQUE INDEX user_actor_unique ON identity_access.users (actor_id)');
        $this->addSql('CREATE UNIQUE INDEX user_email_unique ON identity_access.users (email)');
        $this->addSql('COMMENT ON COLUMN identity_access.users.created_at IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('COMMENT ON COLUMN identity_access.users.updated_at IS \'(DC2Type:datetimetz_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE identity_access.users');
    }
}
