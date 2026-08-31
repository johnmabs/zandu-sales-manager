<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260831180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add physical stock count correction movements';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE inventory.stock_movement DROP CONSTRAINT stock_movement_type_check');
        $this->addSql("ALTER TABLE inventory.stock_movement ADD CONSTRAINT stock_movement_type_check CHECK (type IN ('INITIAL_STOCK','ADJUSTMENT_IN','ADJUSTMENT_OUT','SALE','SALE_RETURN','PURCHASE_RECEIPT','GOODS_RECEIPT_CORRECTION_IN','GOODS_RECEIPT_CORRECTION_OUT','PURCHASE_RETURN','TRANSFER_OUT','TRANSFER_IN','STOCK_COUNT_CORRECTION_IN','STOCK_COUNT_CORRECTION_OUT'))");
        $this->addSql('ALTER TABLE inventory.stock_movement DROP CONSTRAINT stock_movement_source_check');
        $this->addSql("ALTER TABLE inventory.stock_movement ADD CONSTRAINT stock_movement_source_check CHECK (source_type IN ('INITIALIZATION','MANUAL_ADJUSTMENT','SALE','RETURN','GOODS_RECEIPT','GOODS_RECEIPT_CORRECTION','PURCHASE_RETURN','TRANSFER','STOCK_COUNT'))");
        $this->addSql("ALTER TABLE inventory.stock_movement ADD CONSTRAINT stock_movement_stock_count_source_check CHECK ((type IN ('STOCK_COUNT_CORRECTION_IN','STOCK_COUNT_CORRECTION_OUT') AND source_type='STOCK_COUNT' AND source_reference_id IS NOT NULL) OR (type NOT IN ('STOCK_COUNT_CORRECTION_IN','STOCK_COUNT_CORRECTION_OUT') AND source_type<>'STOCK_COUNT'))");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE inventory.stock_movement DROP CONSTRAINT IF EXISTS stock_movement_stock_count_source_check');
        $this->addSql('ALTER TABLE inventory.stock_movement DROP CONSTRAINT stock_movement_type_check');
        $this->addSql("ALTER TABLE inventory.stock_movement ADD CONSTRAINT stock_movement_type_check CHECK (type IN ('INITIAL_STOCK','ADJUSTMENT_IN','ADJUSTMENT_OUT','SALE','SALE_RETURN','PURCHASE_RECEIPT','GOODS_RECEIPT_CORRECTION_IN','GOODS_RECEIPT_CORRECTION_OUT','PURCHASE_RETURN','TRANSFER_OUT','TRANSFER_IN'))");
        $this->addSql('ALTER TABLE inventory.stock_movement DROP CONSTRAINT stock_movement_source_check');
        $this->addSql("ALTER TABLE inventory.stock_movement ADD CONSTRAINT stock_movement_source_check CHECK (source_type IN ('INITIALIZATION','MANUAL_ADJUSTMENT','SALE','RETURN','GOODS_RECEIPT','GOODS_RECEIPT_CORRECTION','PURCHASE_RETURN','TRANSFER'))");
    }
}
