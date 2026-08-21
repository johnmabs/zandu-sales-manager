<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260823030000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow the tenant runtime to register and load global user accounts';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('GRANT SELECT, INSERT, UPDATE ON identity_access.users TO zandu_runtime');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('REVOKE SELECT, INSERT, UPDATE ON identity_access.users FROM zandu_runtime');
    }
}
