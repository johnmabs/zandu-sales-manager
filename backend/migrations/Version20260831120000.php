<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260831120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Support physical stock transfer movements';
    }
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE inventory.stock_movement DROP CONSTRAINT stock_movement_type_check');
        $this->addSql("ALTER TABLE inventory.stock_movement ADD CONSTRAINT stock_movement_type_check CHECK (type IN ('INITIAL_STOCK','ADJUSTMENT_IN','ADJUSTMENT_OUT','SALE','SALE_RETURN','PURCHASE_RECEIPT','GOODS_RECEIPT_CORRECTION_IN','GOODS_RECEIPT_CORRECTION_OUT','PURCHASE_RETURN','TRANSFER_OUT','TRANSFER_IN'))");
        $this->addSql('ALTER TABLE inventory.stock_movement DROP CONSTRAINT stock_movement_source_check');
        $this->addSql("ALTER TABLE inventory.stock_movement ADD CONSTRAINT stock_movement_source_check CHECK (source_type IN ('INITIALIZATION','MANUAL_ADJUSTMENT','SALE','RETURN','GOODS_RECEIPT','GOODS_RECEIPT_CORRECTION','PURCHASE_RETURN','TRANSFER'))");
        $this->addSql('DROP INDEX inventory.stock_movement_source_idempotency_idx');
        $this->addSql('CREATE UNIQUE INDEX stock_movement_source_idempotency_idx ON inventory.stock_movement (organization_id,product_id,type,source_type,source_reference_id) WHERE source_reference_id IS NOT NULL');
    }
    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM inventory.stock_movement WHERE type IN ('TRANSFER_OUT','TRANSFER_IN')");
        $this->addSql('DROP INDEX inventory.stock_movement_source_idempotency_idx');
        $this->addSql('CREATE UNIQUE INDEX stock_movement_source_idempotency_idx ON inventory.stock_movement (organization_id,product_id,source_type,source_reference_id) WHERE source_reference_id IS NOT NULL');
        $this->addSql('ALTER TABLE inventory.stock_movement DROP CONSTRAINT stock_movement_type_check');
        $this->addSql("ALTER TABLE inventory.stock_movement ADD CONSTRAINT stock_movement_type_check CHECK (type IN ('INITIAL_STOCK','ADJUSTMENT_IN','ADJUSTMENT_OUT','SALE','SALE_RETURN','PURCHASE_RECEIPT','GOODS_RECEIPT_CORRECTION_IN','GOODS_RECEIPT_CORRECTION_OUT','PURCHASE_RETURN'))");
        $this->addSql('ALTER TABLE inventory.stock_movement DROP CONSTRAINT stock_movement_source_check');
        $this->addSql("ALTER TABLE inventory.stock_movement ADD CONSTRAINT stock_movement_source_check CHECK (source_type IN ('INITIALIZATION','MANUAL_ADJUSTMENT','SALE','RETURN','GOODS_RECEIPT','GOODS_RECEIPT_CORRECTION','PURCHASE_RETURN'))");
    }
}
