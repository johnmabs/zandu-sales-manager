<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260901000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Value stock count correction movements';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE inventory_costing.stock_valuation_movement DROP CONSTRAINT stock_valuation_movement_type_check, DROP CONSTRAINT stock_valuation_movement_source_check, DROP CONSTRAINT stock_valuation_movement_total_check');
        $this->addSql("ALTER TABLE inventory_costing.stock_valuation_movement ADD CONSTRAINT stock_valuation_movement_type_check CHECK (type IN ('OPENING','INITIAL_STOCK','ADJUSTMENT_IN','ADJUSTMENT_OUT','SALE','SALE_RETURN','PURCHASE_RECEIPT','GOODS_RECEIPT_CORRECTION_IN','GOODS_RECEIPT_CORRECTION_OUT','PURCHASE_RETURN','TRANSFER_OUT','TRANSFER_IN','STOCK_COUNT_CORRECTION_IN','STOCK_COUNT_CORRECTION_OUT'))");
        $this->addSql("ALTER TABLE inventory_costing.stock_valuation_movement ADD CONSTRAINT stock_valuation_movement_source_check CHECK ((type = 'OPENING' AND source_type = 'BOOTSTRAP') OR (type = 'INITIAL_STOCK' AND source_type = 'INITIALIZATION') OR (type IN ('ADJUSTMENT_IN','ADJUSTMENT_OUT') AND source_type = 'MANUAL_ADJUSTMENT') OR (type = 'SALE' AND source_type = 'SALE') OR (type = 'SALE_RETURN' AND source_type = 'RETURN') OR (type = 'PURCHASE_RECEIPT' AND source_type = 'GOODS_RECEIPT') OR (type IN ('GOODS_RECEIPT_CORRECTION_IN','GOODS_RECEIPT_CORRECTION_OUT') AND source_type = 'GOODS_RECEIPT_CORRECTION') OR (type = 'PURCHASE_RETURN' AND source_type = 'PURCHASE_RETURN') OR (type IN ('TRANSFER_OUT','TRANSFER_IN') AND source_type = 'TRANSFER') OR (type IN ('STOCK_COUNT_CORRECTION_IN','STOCK_COUNT_CORRECTION_OUT') AND source_type = 'STOCK_COUNT'))");
        $this->addSql("ALTER TABLE inventory_costing.stock_valuation_movement ADD CONSTRAINT stock_valuation_movement_total_check CHECK ((type IN ('OPENING','INITIAL_STOCK','ADJUSTMENT_IN','SALE_RETURN','PURCHASE_RECEIPT','GOODS_RECEIPT_CORRECTION_IN','TRANSFER_IN','STOCK_COUNT_CORRECTION_IN') AND resulting_total_value = previous_total_value + value) OR (type IN ('ADJUSTMENT_OUT','SALE','GOODS_RECEIPT_CORRECTION_OUT','PURCHASE_RETURN','TRANSFER_OUT','STOCK_COUNT_CORRECTION_OUT') AND resulting_total_value = previous_total_value - value))");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM inventory_costing.stock_valuation_movement WHERE type IN ('STOCK_COUNT_CORRECTION_IN','STOCK_COUNT_CORRECTION_OUT')");
        $this->addSql('ALTER TABLE inventory_costing.stock_valuation_movement DROP CONSTRAINT stock_valuation_movement_type_check, DROP CONSTRAINT stock_valuation_movement_source_check, DROP CONSTRAINT stock_valuation_movement_total_check');
        $this->addSql("ALTER TABLE inventory_costing.stock_valuation_movement ADD CONSTRAINT stock_valuation_movement_type_check CHECK (type IN ('OPENING','INITIAL_STOCK','ADJUSTMENT_IN','ADJUSTMENT_OUT','SALE','SALE_RETURN','PURCHASE_RECEIPT','GOODS_RECEIPT_CORRECTION_IN','GOODS_RECEIPT_CORRECTION_OUT','PURCHASE_RETURN','TRANSFER_OUT','TRANSFER_IN'))");
        $this->addSql("ALTER TABLE inventory_costing.stock_valuation_movement ADD CONSTRAINT stock_valuation_movement_source_check CHECK ((type = 'OPENING' AND source_type = 'BOOTSTRAP') OR (type = 'INITIAL_STOCK' AND source_type = 'INITIALIZATION') OR (type IN ('ADJUSTMENT_IN','ADJUSTMENT_OUT') AND source_type = 'MANUAL_ADJUSTMENT') OR (type = 'SALE' AND source_type = 'SALE') OR (type = 'SALE_RETURN' AND source_type = 'RETURN') OR (type = 'PURCHASE_RECEIPT' AND source_type = 'GOODS_RECEIPT') OR (type IN ('GOODS_RECEIPT_CORRECTION_IN','GOODS_RECEIPT_CORRECTION_OUT') AND source_type = 'GOODS_RECEIPT_CORRECTION') OR (type = 'PURCHASE_RETURN' AND source_type = 'PURCHASE_RETURN') OR (type IN ('TRANSFER_OUT','TRANSFER_IN') AND source_type = 'TRANSFER'))");
        $this->addSql("ALTER TABLE inventory_costing.stock_valuation_movement ADD CONSTRAINT stock_valuation_movement_total_check CHECK ((type IN ('OPENING','INITIAL_STOCK','ADJUSTMENT_IN','SALE_RETURN','PURCHASE_RECEIPT','GOODS_RECEIPT_CORRECTION_IN','TRANSFER_IN') AND resulting_total_value = previous_total_value + value) OR (type IN ('ADJUSTMENT_OUT','SALE','GOODS_RECEIPT_CORRECTION_OUT','PURCHASE_RETURN','TRANSFER_OUT') AND resulting_total_value = previous_total_value - value))");
    }
}
