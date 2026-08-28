<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260828140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow idempotent physical stock movements for sale returns';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE inventory.stock_movement DROP CONSTRAINT stock_movement_type_check');
        $this->addSql("ALTER TABLE inventory.stock_movement ADD CONSTRAINT stock_movement_type_check CHECK (type IN ('INITIAL_STOCK', 'ADJUSTMENT_IN', 'ADJUSTMENT_OUT', 'SALE', 'SALE_RETURN'))");
        $this->addSql('ALTER TABLE inventory.stock_movement DROP CONSTRAINT stock_movement_source_check');
        $this->addSql("ALTER TABLE inventory.stock_movement ADD CONSTRAINT stock_movement_source_check CHECK (source_type IN ('INITIALIZATION', 'MANUAL_ADJUSTMENT', 'SALE', 'RETURN'))");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM inventory.stock_movement WHERE type = 'SALE_RETURN' OR source_type = 'RETURN'");
        $this->addSql('ALTER TABLE inventory.stock_movement DROP CONSTRAINT stock_movement_type_check');
        $this->addSql("ALTER TABLE inventory.stock_movement ADD CONSTRAINT stock_movement_type_check CHECK (type IN ('INITIAL_STOCK', 'ADJUSTMENT_IN', 'ADJUSTMENT_OUT', 'SALE'))");
        $this->addSql('ALTER TABLE inventory.stock_movement DROP CONSTRAINT stock_movement_source_check');
        $this->addSql("ALTER TABLE inventory.stock_movement ADD CONSTRAINT stock_movement_source_check CHECK (source_type IN ('INITIALIZATION', 'MANUAL_ADJUSTMENT', 'SALE'))");
    }
}
