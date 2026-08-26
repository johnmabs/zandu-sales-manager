<?php
declare(strict_types=1);
namespace DoctrineMigrations;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
final class Version20260826100000 extends AbstractMigration
{
    public function getDescription(): string { return 'Enforce idempotent inventory movement sources'; }
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE UNIQUE INDEX stock_movement_source_idempotency_idx ON inventory.stock_movement (organization_id, product_id, source_type, source_reference_id) WHERE source_reference_id IS NOT NULL');
    }
    public function down(Schema $schema): void { $this->addSql('DROP INDEX stock_movement_source_idempotency_idx'); }
}
