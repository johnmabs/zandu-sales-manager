<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260831140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Transfer inventory value between stores';
    }
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE inventory.stock_transfer_line ADD shipped_unit_cost_snapshot NUMERIC(30,12) NULL, ADD shipped_value_snapshot NUMERIC(30,6) NULL, ADD received_value_snapshot NUMERIC(30,6) NULL, ADD cost_currency CHAR(3) NULL');
        $this->addSql('ALTER TABLE inventory.stock_transfer_line ADD CONSTRAINT stock_transfer_line_cost_check CHECK ((shipped_unit_cost_snapshot IS NULL AND shipped_value_snapshot IS NULL AND cost_currency IS NULL) OR (shipped_unit_cost_snapshot >= 0 AND shipped_value_snapshot >= 0 AND cost_currency IS NOT NULL)), ADD CONSTRAINT stock_transfer_line_received_value_check CHECK (received_value_snapshot IS NULL OR (received_value_snapshot >= 0 AND shipped_value_snapshot IS NOT NULL AND received_value_snapshot <= shipped_value_snapshot))');
        $this->addSql('ALTER TABLE inventory_costing.stock_valuation_movement DROP CONSTRAINT stock_valuation_movement_type_check');
        $this->addSql("ALTER TABLE inventory_costing.stock_valuation_movement ADD CONSTRAINT stock_valuation_movement_type_check CHECK (type IN ('OPENING','INITIAL_STOCK','ADJUSTMENT_IN','ADJUSTMENT_OUT','SALE','SALE_RETURN','PURCHASE_RECEIPT','GOODS_RECEIPT_CORRECTION_IN','GOODS_RECEIPT_CORRECTION_OUT','PURCHASE_RETURN','TRANSFER_OUT','TRANSFER_IN'))");
        $this->addSql('ALTER TABLE inventory_costing.stock_valuation_movement DROP CONSTRAINT stock_valuation_movement_source_check');
        $this->addSql("ALTER TABLE inventory_costing.stock_valuation_movement ADD CONSTRAINT stock_valuation_movement_source_check CHECK ((type = 'OPENING' AND source_type = 'BOOTSTRAP') OR (type = 'INITIAL_STOCK' AND source_type = 'INITIALIZATION') OR (type IN ('ADJUSTMENT_IN','ADJUSTMENT_OUT') AND source_type = 'MANUAL_ADJUSTMENT') OR (type = 'SALE' AND source_type = 'SALE') OR (type = 'SALE_RETURN' AND source_type = 'RETURN') OR (type = 'PURCHASE_RECEIPT' AND source_type = 'GOODS_RECEIPT') OR (type IN ('GOODS_RECEIPT_CORRECTION_IN','GOODS_RECEIPT_CORRECTION_OUT') AND source_type = 'GOODS_RECEIPT_CORRECTION') OR (type = 'PURCHASE_RETURN' AND source_type = 'PURCHASE_RETURN') OR (type IN ('TRANSFER_OUT','TRANSFER_IN') AND source_type = 'TRANSFER'))");
        $this->addSql('ALTER TABLE inventory_costing.stock_valuation_movement DROP CONSTRAINT stock_valuation_movement_total_check');
        $this->addSql("ALTER TABLE inventory_costing.stock_valuation_movement ADD CONSTRAINT stock_valuation_movement_total_check CHECK ((type IN ('OPENING','INITIAL_STOCK','ADJUSTMENT_IN','SALE_RETURN','PURCHASE_RECEIPT','GOODS_RECEIPT_CORRECTION_IN','TRANSFER_IN') AND resulting_total_value = previous_total_value + value) OR (type IN ('ADJUSTMENT_OUT','SALE','GOODS_RECEIPT_CORRECTION_OUT','PURCHASE_RETURN','TRANSFER_OUT') AND resulting_total_value = previous_total_value - value))");
    }
    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM inventory_costing.stock_valuation_movement WHERE type IN ('TRANSFER_OUT','TRANSFER_IN')");
        $this->addSql('ALTER TABLE inventory_costing.stock_valuation_movement DROP CONSTRAINT stock_valuation_movement_type_check, DROP CONSTRAINT stock_valuation_movement_source_check, DROP CONSTRAINT stock_valuation_movement_total_check');
        $this->addSql("ALTER TABLE inventory_costing.stock_valuation_movement ADD CONSTRAINT stock_valuation_movement_type_check CHECK (type IN ('OPENING','INITIAL_STOCK','ADJUSTMENT_IN','ADJUSTMENT_OUT','SALE','SALE_RETURN','PURCHASE_RECEIPT','GOODS_RECEIPT_CORRECTION_IN','GOODS_RECEIPT_CORRECTION_OUT','PURCHASE_RETURN'))");
        $this->addSql("ALTER TABLE inventory_costing.stock_valuation_movement ADD CONSTRAINT stock_valuation_movement_source_check CHECK ((type = 'OPENING' AND source_type = 'BOOTSTRAP') OR (type = 'INITIAL_STOCK' AND source_type = 'INITIALIZATION') OR (type IN ('ADJUSTMENT_IN','ADJUSTMENT_OUT') AND source_type = 'MANUAL_ADJUSTMENT') OR (type = 'SALE' AND source_type = 'SALE') OR (type = 'SALE_RETURN' AND source_type = 'RETURN') OR (type = 'PURCHASE_RECEIPT' AND source_type = 'GOODS_RECEIPT') OR (type IN ('GOODS_RECEIPT_CORRECTION_IN','GOODS_RECEIPT_CORRECTION_OUT') AND source_type = 'GOODS_RECEIPT_CORRECTION') OR (type = 'PURCHASE_RETURN' AND source_type = 'PURCHASE_RETURN'))");
        $this->addSql("ALTER TABLE inventory_costing.stock_valuation_movement ADD CONSTRAINT stock_valuation_movement_total_check CHECK ((type IN ('OPENING','INITIAL_STOCK','ADJUSTMENT_IN','SALE_RETURN','PURCHASE_RECEIPT','GOODS_RECEIPT_CORRECTION_IN') AND resulting_total_value = previous_total_value + value) OR (type IN ('ADJUSTMENT_OUT','SALE','GOODS_RECEIPT_CORRECTION_OUT','PURCHASE_RETURN') AND resulting_total_value = previous_total_value - value))");
        $this->addSql('ALTER TABLE inventory.stock_transfer_line DROP CONSTRAINT stock_transfer_line_cost_check, DROP CONSTRAINT stock_transfer_line_received_value_check, DROP shipped_unit_cost_snapshot, DROP shipped_value_snapshot, DROP received_value_snapshot, DROP cost_currency');
    }
}
