<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260831190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Enforce stock count lifecycle audits and completed progress';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE inventory.stock_count ADD CONSTRAINT stock_count_audit_pairs_check CHECK ((started_by IS NULL)=(started_at IS NULL) AND (finalization_started_by IS NULL)=(finalization_started_at IS NULL) AND (completed_by IS NULL)=(completed_at IS NULL) AND (cancelled_by IS NULL)=(cancelled_at IS NULL))");
        $this->addSql(<<<'SQL'
ALTER TABLE inventory.stock_count ADD CONSTRAINT stock_count_lifecycle_check CHECK (
 (status='DRAFT' AND started_by IS NULL AND finalization_started_by IS NULL AND completed_by IS NULL AND cancelled_by IS NULL)
 OR (status='OPEN' AND started_by IS NOT NULL AND finalization_started_by IS NULL AND completed_by IS NULL AND cancelled_by IS NULL)
 OR (status='FINALIZING' AND started_by IS NOT NULL AND finalization_started_by IS NOT NULL AND completed_by IS NULL AND cancelled_by IS NULL)
 OR (status='COMPLETED' AND started_by IS NOT NULL AND finalization_started_by IS NOT NULL AND completed_by IS NOT NULL AND cancelled_by IS NULL AND total_line_count=counted_line_count AND total_line_count=reconciled_line_count)
 OR (status='CANCELLED' AND finalization_started_by IS NULL AND completed_by IS NULL AND cancelled_by IS NOT NULL)
)
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE inventory.stock_count DROP CONSTRAINT stock_count_lifecycle_check');
        $this->addSql('ALTER TABLE inventory.stock_count DROP CONSTRAINT stock_count_audit_pairs_check');
    }
}
