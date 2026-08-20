<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260820223000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create stateful refresh token sessions in the Identity and Access schema';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA IF NOT EXISTS identity_access');
        $this->addSql(<<<'SQL'
CREATE TABLE identity_access.refresh_session (
    id UUID NOT NULL,
    user_identifier VARCHAR(180) NOT NULL,
    current_token_hash CHAR(64) NOT NULL,
    used_token_hashes JSONB NOT NULL DEFAULT '[]'::jsonb,
    expires_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
    revoked_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL,
    PRIMARY KEY (id)
)
SQL);
        $this->addSql('CREATE INDEX refresh_session_user_idx ON identity_access.refresh_session (user_identifier)');
        $this->addSql('COMMENT ON COLUMN identity_access.refresh_session.expires_at IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('COMMENT ON COLUMN identity_access.refresh_session.revoked_at IS \'(DC2Type:datetimetz_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE identity_access.refresh_session');
        $this->addSql('DROP SCHEMA IF EXISTS identity_access');
    }
}
