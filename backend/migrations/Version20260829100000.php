<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260829100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Support purchase receipt movements in the inventory costing ledger';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE inventory_costing.stock_valuation_movement DROP CONSTRAINT stock_valuation_movement_type_check');
        $this->addSql("ALTER TABLE inventory_costing.stock_valuation_movement ADD CONSTRAINT stock_valuation_movement_type_check CHECK (type IN ('OPENING', 'INITIAL_STOCK', 'ADJUSTMENT_IN', 'ADJUSTMENT_OUT', 'SALE', 'SALE_RETURN', 'PURCHASE_RECEIPT'))");
        $this->addSql('ALTER TABLE inventory_costing.stock_valuation_movement DROP CONSTRAINT stock_valuation_movement_source_check');
        $this->addSql("ALTER TABLE inventory_costing.stock_valuation_movement ADD CONSTRAINT stock_valuation_movement_source_check CHECK ((type = 'OPENING' AND source_type = 'BOOTSTRAP') OR (type = 'INITIAL_STOCK' AND source_type = 'INITIALIZATION') OR (type IN ('ADJUSTMENT_IN', 'ADJUSTMENT_OUT') AND source_type = 'MANUAL_ADJUSTMENT') OR (type = 'SALE' AND source_type = 'SALE') OR (type = 'SALE_RETURN' AND source_type = 'RETURN') OR (type = 'PURCHASE_RECEIPT' AND source_type = 'GOODS_RECEIPT'))");
        $this->addSql('ALTER TABLE inventory_costing.stock_valuation_movement DROP CONSTRAINT stock_valuation_movement_total_check');
        $this->addSql("ALTER TABLE inventory_costing.stock_valuation_movement ADD CONSTRAINT stock_valuation_movement_total_check CHECK ((type IN ('OPENING', 'INITIAL_STOCK', 'ADJUSTMENT_IN', 'SALE_RETURN', 'PURCHASE_RECEIPT') AND resulting_total_value = previous_total_value + value) OR (type IN ('ADJUSTMENT_OUT', 'SALE') AND resulting_total_value = previous_total_value - value))");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM inventory_costing.stock_valuation_movement WHERE type = 'PURCHASE_RECEIPT'");
        $this->addSql('ALTER TABLE inventory_costing.stock_valuation_movement DROP CONSTRAINT stock_valuation_movement_type_check');
        $this->addSql("ALTER TABLE inventory_costing.stock_valuation_movement ADD CONSTRAINT stock_valuation_movement_type_check CHECK (type IN ('OPENING', 'INITIAL_STOCK', 'ADJUSTMENT_IN', 'ADJUSTMENT_OUT', 'SALE', 'SALE_RETURN'))");
        $this->addSql('ALTER TABLE inventory_costing.stock_valuation_movement DROP CONSTRAINT stock_valuation_movement_source_check');
        $this->addSql("ALTER TABLE inventory_costing.stock_valuation_movement ADD CONSTRAINT stock_valuation_movement_source_check CHECK ((type = 'OPENING' AND source_type = 'BOOTSTRAP') OR (type = 'INITIAL_STOCK' AND source_type = 'INITIALIZATION') OR (type IN ('ADJUSTMENT_IN', 'ADJUSTMENT_OUT') AND source_type = 'MANUAL_ADJUSTMENT') OR (type = 'SALE' AND source_type = 'SALE') OR (type = 'SALE_RETURN' AND source_type = 'RETURN'))");
        $this->addSql('ALTER TABLE inventory_costing.stock_valuation_movement DROP CONSTRAINT stock_valuation_movement_total_check');
        $this->addSql("ALTER TABLE inventory_costing.stock_valuation_movement ADD CONSTRAINT stock_valuation_movement_total_check CHECK ((type IN ('OPENING', 'INITIAL_STOCK', 'ADJUSTMENT_IN', 'SALE_RETURN') AND resulting_total_value = previous_total_value + value) OR (type IN ('ADJUSTMENT_OUT', 'SALE') AND resulting_total_value = previous_total_value - value))");
    }
}
