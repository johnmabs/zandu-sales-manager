<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260824223000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Bind refresh sessions to their active organization and authorization version';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE identity_access.refresh_session ADD organization_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE identity_access.refresh_session ADD authorization_version INT DEFAULT NULL');
        $this->addSql(<<<'SQL'
UPDATE identity_access.refresh_session session
SET organization_id = users.default_organization_id,
    authorization_version = memberships.authorization_version
FROM identity_access.users users
JOIN identity_access.organization_memberships memberships
  ON memberships.organization_id = users.default_organization_id
 AND memberships.user_id = users.id
WHERE users.email = session.user_identifier
  AND memberships.status = 'ACTIVE'
SQL);
        $this->addSql('DELETE FROM identity_access.refresh_session WHERE organization_id IS NULL OR authorization_version IS NULL');
        $this->addSql('ALTER TABLE identity_access.refresh_session ALTER organization_id SET NOT NULL');
        $this->addSql('ALTER TABLE identity_access.refresh_session ALTER authorization_version SET NOT NULL');
        $this->addSql('ALTER TABLE identity_access.refresh_session ADD CONSTRAINT refresh_session_organization_fk FOREIGN KEY (organization_id) REFERENCES organization.organizations (id)');
        $this->addSql('CREATE INDEX refresh_session_tenant_user_idx ON identity_access.refresh_session (organization_id, user_identifier)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX identity_access.refresh_session_tenant_user_idx');
        $this->addSql('ALTER TABLE identity_access.refresh_session DROP CONSTRAINT refresh_session_organization_fk');
        $this->addSql('ALTER TABLE identity_access.refresh_session DROP authorization_version');
        $this->addSql('ALTER TABLE identity_access.refresh_session DROP organization_id');
    }
}
