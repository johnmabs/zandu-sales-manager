<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260826120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Grant runtime role usage on Lot 3 schemas';
    }
    public function up(Schema $schema): void
    {
        $this->addSql('GRANT USAGE ON SCHEMA inventory TO zandu_runtime');
        $this->addSql('GRANT USAGE ON SCHEMA cash_management TO zandu_runtime');
    }
    public function down(Schema $schema): void
    {
        $this->addSql('REVOKE USAGE ON SCHEMA inventory FROM zandu_runtime');
        $this->addSql('REVOKE USAGE ON SCHEMA cash_management FROM zandu_runtime');
    }
}
