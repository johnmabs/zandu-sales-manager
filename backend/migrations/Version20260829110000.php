<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260829110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Support idempotent physical stock movements for supplier goods receipts';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE inventory.stock_movement DROP CONSTRAINT stock_movement_type_check');
        $this->addSql("ALTER TABLE inventory.stock_movement ADD CONSTRAINT stock_movement_type_check CHECK (type IN ('INITIAL_STOCK', 'ADJUSTMENT_IN', 'ADJUSTMENT_OUT', 'SALE', 'SALE_RETURN', 'PURCHASE_RECEIPT'))");
        $this->addSql('ALTER TABLE inventory.stock_movement DROP CONSTRAINT stock_movement_source_check');
        $this->addSql("ALTER TABLE inventory.stock_movement ADD CONSTRAINT stock_movement_source_check CHECK (source_type IN ('INITIALIZATION', 'MANUAL_ADJUSTMENT', 'SALE', 'RETURN', 'GOODS_RECEIPT'))");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM inventory_costing.stock_valuation_movement WHERE type = 'PURCHASE_RECEIPT'");
        $this->addSql("DELETE FROM inventory.stock_movement WHERE type = 'PURCHASE_RECEIPT' OR source_type = 'GOODS_RECEIPT'");
        $this->addSql('ALTER TABLE inventory.stock_movement DROP CONSTRAINT stock_movement_type_check');
        $this->addSql("ALTER TABLE inventory.stock_movement ADD CONSTRAINT stock_movement_type_check CHECK (type IN ('INITIAL_STOCK', 'ADJUSTMENT_IN', 'ADJUSTMENT_OUT', 'SALE', 'SALE_RETURN'))");
        $this->addSql('ALTER TABLE inventory.stock_movement DROP CONSTRAINT stock_movement_source_check');
        $this->addSql("ALTER TABLE inventory.stock_movement ADD CONSTRAINT stock_movement_source_check CHECK (source_type IN ('INITIALIZATION', 'MANUAL_ADJUSTMENT', 'SALE', 'RETURN'))");
    }
}
