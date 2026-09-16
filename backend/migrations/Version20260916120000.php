<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260916120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Enable Doctrine optimistic locking for Store, OrganizationMembership, and OrganizationInvitation';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE organization.stores ALTER COLUMN version SET DEFAULT 1');
        $this->addSql('ALTER TABLE identity_access.organization_memberships ALTER COLUMN version SET DEFAULT 1');
        $this->addSql('ALTER TABLE identity_access.organization_invitations ALTER COLUMN version SET DEFAULT 1');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE organization.stores ALTER COLUMN version DROP DEFAULT');
        $this->addSql('ALTER TABLE identity_access.organization_memberships ALTER COLUMN version DROP DEFAULT');
        $this->addSql('ALTER TABLE identity_access.organization_invitations ALTER COLUMN version DROP DEFAULT');
    }
}
