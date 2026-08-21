<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260822210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add audited lifecycle metadata to organization memberships';
    }
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE identity_access.organization_memberships ADD suspended_by UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE identity_access.organization_memberships ADD suspended_at TIMESTAMPTZ DEFAULT NULL');
        $this->addSql('ALTER TABLE identity_access.organization_memberships ADD revoked_by UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE identity_access.organization_memberships ADD revoked_at TIMESTAMPTZ DEFAULT NULL');
    }
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE identity_access.organization_memberships DROP suspended_by, DROP suspended_at, DROP revoked_by, DROP revoked_at');
    }
}
