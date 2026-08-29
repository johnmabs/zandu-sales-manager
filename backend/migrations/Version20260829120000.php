<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260829120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow explicitly authorized purchase order over receipts';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE purchasing.purchase_order_line DROP CONSTRAINT purchase_order_line_received_check');
        $this->addSql('ALTER TABLE purchasing.purchase_order_line ADD CONSTRAINT purchase_order_line_received_check CHECK (received_quantity >= 0)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE purchasing.purchase_order_line DROP CONSTRAINT purchase_order_line_received_check');
        $this->addSql('ALTER TABLE purchasing.purchase_order_line ADD CONSTRAINT purchase_order_line_received_check CHECK (received_quantity >= 0 AND received_quantity <= ordered_base_quantity)');
    }
}
