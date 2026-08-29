<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260829140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Support physical and valued goods receipt correction movements';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE inventory.stock_movement DROP CONSTRAINT stock_movement_type_check');
        $this->addSql("ALTER TABLE inventory.stock_movement ADD CONSTRAINT stock_movement_type_check CHECK (type IN ('INITIAL_STOCK', 'ADJUSTMENT_IN', 'ADJUSTMENT_OUT', 'SALE', 'SALE_RETURN', 'PURCHASE_RECEIPT', 'GOODS_RECEIPT_CORRECTION_IN', 'GOODS_RECEIPT_CORRECTION_OUT'))");
        $this->addSql('ALTER TABLE inventory.stock_movement DROP CONSTRAINT stock_movement_source_check');
        $this->addSql("ALTER TABLE inventory.stock_movement ADD CONSTRAINT stock_movement_source_check CHECK (source_type IN ('INITIALIZATION', 'MANUAL_ADJUSTMENT', 'SALE', 'RETURN', 'GOODS_RECEIPT', 'GOODS_RECEIPT_CORRECTION'))");
        $this->addSql('ALTER TABLE inventory_costing.stock_valuation_movement DROP CONSTRAINT stock_valuation_movement_type_check');
        $this->addSql("ALTER TABLE inventory_costing.stock_valuation_movement ADD CONSTRAINT stock_valuation_movement_type_check CHECK (type IN ('OPENING', 'INITIAL_STOCK', 'ADJUSTMENT_IN', 'ADJUSTMENT_OUT', 'SALE', 'SALE_RETURN', 'PURCHASE_RECEIPT', 'GOODS_RECEIPT_CORRECTION_IN', 'GOODS_RECEIPT_CORRECTION_OUT'))");
        $this->addSql('ALTER TABLE inventory_costing.stock_valuation_movement DROP CONSTRAINT stock_valuation_movement_source_check');
        $this->addSql("ALTER TABLE inventory_costing.stock_valuation_movement ADD CONSTRAINT stock_valuation_movement_source_check CHECK ((type = 'OPENING' AND source_type = 'BOOTSTRAP') OR (type = 'INITIAL_STOCK' AND source_type = 'INITIALIZATION') OR (type IN ('ADJUSTMENT_IN', 'ADJUSTMENT_OUT') AND source_type = 'MANUAL_ADJUSTMENT') OR (type = 'SALE' AND source_type = 'SALE') OR (type = 'SALE_RETURN' AND source_type = 'RETURN') OR (type = 'PURCHASE_RECEIPT' AND source_type = 'GOODS_RECEIPT') OR (type IN ('GOODS_RECEIPT_CORRECTION_IN', 'GOODS_RECEIPT_CORRECTION_OUT') AND source_type = 'GOODS_RECEIPT_CORRECTION'))");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM inventory_costing.stock_valuation_movement WHERE type IN ('GOODS_RECEIPT_CORRECTION_IN', 'GOODS_RECEIPT_CORRECTION_OUT')");
        $this->addSql("DELETE FROM inventory.stock_movement WHERE type IN ('GOODS_RECEIPT_CORRECTION_IN', 'GOODS_RECEIPT_CORRECTION_OUT') OR source_type = 'GOODS_RECEIPT_CORRECTION'");
        $this->addSql('ALTER TABLE inventory.stock_movement DROP CONSTRAINT stock_movement_type_check');
        $this->addSql("ALTER TABLE inventory.stock_movement ADD CONSTRAINT stock_movement_type_check CHECK (type IN ('INITIAL_STOCK', 'ADJUSTMENT_IN', 'ADJUSTMENT_OUT', 'SALE', 'SALE_RETURN', 'PURCHASE_RECEIPT'))");
        $this->addSql('ALTER TABLE inventory.stock_movement DROP CONSTRAINT stock_movement_source_check');
        $this->addSql("ALTER TABLE inventory.stock_movement ADD CONSTRAINT stock_movement_source_check CHECK (source_type IN ('INITIALIZATION', 'MANUAL_ADJUSTMENT', 'SALE', 'RETURN', 'GOODS_RECEIPT'))");
        $this->addSql('ALTER TABLE inventory_costing.stock_valuation_movement DROP CONSTRAINT stock_valuation_movement_type_check');
        $this->addSql("ALTER TABLE inventory_costing.stock_valuation_movement ADD CONSTRAINT stock_valuation_movement_type_check CHECK (type IN ('OPENING', 'INITIAL_STOCK', 'ADJUSTMENT_IN', 'ADJUSTMENT_OUT', 'SALE', 'SALE_RETURN', 'PURCHASE_RECEIPT'))");
        $this->addSql('ALTER TABLE inventory_costing.stock_valuation_movement DROP CONSTRAINT stock_valuation_movement_source_check');
        $this->addSql("ALTER TABLE inventory_costing.stock_valuation_movement ADD CONSTRAINT stock_valuation_movement_source_check CHECK ((type = 'OPENING' AND source_type = 'BOOTSTRAP') OR (type = 'INITIAL_STOCK' AND source_type = 'INITIALIZATION') OR (type IN ('ADJUSTMENT_IN', 'ADJUSTMENT_OUT') AND source_type = 'MANUAL_ADJUSTMENT') OR (type = 'SALE' AND source_type = 'SALE') OR (type = 'SALE_RETURN' AND source_type = 'RETURN') OR (type = 'PURCHASE_RECEIPT' AND source_type = 'GOODS_RECEIPT'))");
    }
}
