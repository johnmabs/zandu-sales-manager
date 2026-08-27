<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260827090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Enforce store consistency across cash registers, sessions and movements';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cash_management.cash_register ADD CONSTRAINT cash_register_tenant_store_id_unique UNIQUE (organization_id, store_id, id)');
        $this->addSql('ALTER TABLE cash_management.cash_session ADD CONSTRAINT cash_session_tenant_store_id_unique UNIQUE (organization_id, store_id, id)');
        $this->addSql('ALTER TABLE cash_management.cash_session DROP CONSTRAINT cash_session_register_fk');
        $this->addSql('ALTER TABLE cash_management.cash_session ADD CONSTRAINT cash_session_register_store_fk FOREIGN KEY (organization_id, store_id, cash_register_id) REFERENCES cash_management.cash_register (organization_id, store_id, id)');
        $this->addSql('ALTER TABLE cash_management.cash_movement DROP CONSTRAINT cash_movement_session_fk');
        $this->addSql('ALTER TABLE cash_management.cash_movement ADD CONSTRAINT cash_movement_session_store_fk FOREIGN KEY (organization_id, store_id, cash_session_id) REFERENCES cash_management.cash_session (organization_id, store_id, id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cash_management.cash_movement DROP CONSTRAINT cash_movement_session_store_fk');
        $this->addSql('ALTER TABLE cash_management.cash_movement ADD CONSTRAINT cash_movement_session_fk FOREIGN KEY (organization_id, cash_session_id) REFERENCES cash_management.cash_session (organization_id, id)');
        $this->addSql('ALTER TABLE cash_management.cash_session DROP CONSTRAINT cash_session_register_store_fk');
        $this->addSql('ALTER TABLE cash_management.cash_session ADD CONSTRAINT cash_session_register_fk FOREIGN KEY (organization_id, cash_register_id) REFERENCES cash_management.cash_register (organization_id, id)');
        $this->addSql('ALTER TABLE cash_management.cash_session DROP CONSTRAINT cash_session_tenant_store_id_unique');
        $this->addSql('ALTER TABLE cash_management.cash_register DROP CONSTRAINT cash_register_tenant_store_id_unique');
    }
}
