<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260828180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Persist tenant-scoped immutable cash payment refunds';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
CREATE TABLE payments.payment_refund (
    id UUID PRIMARY KEY,
    organization_id UUID NOT NULL,
    payment_id UUID NOT NULL,
    return_sale_id UUID NOT NULL,
    cash_session_id UUID NOT NULL,
    status VARCHAR(16) NOT NULL,
    amount NUMERIC(30,12) NOT NULL,
    currency VARCHAR(3) NOT NULL,
    reason VARCHAR(500) NULL,
    idempotency_key VARCHAR(255) NOT NULL,
    payload_hash CHAR(64) NOT NULL,
    created_by UUID NOT NULL,
    created_at TIMESTAMPTZ NOT NULL,
    confirmed_at TIMESTAMPTZ NULL,
    version INT NOT NULL,
    CONSTRAINT payment_refund_tenant_id_unique UNIQUE (organization_id, id),
    CONSTRAINT payment_refund_payment_fk FOREIGN KEY (organization_id, payment_id)
        REFERENCES payments.payment (organization_id, id) ON DELETE RESTRICT,
    CONSTRAINT payment_refund_return_fk FOREIGN KEY (organization_id, return_sale_id)
        REFERENCES sales.return_sale (organization_id, id) ON DELETE RESTRICT,
    CONSTRAINT payment_refund_session_fk FOREIGN KEY (organization_id, cash_session_id)
        REFERENCES cash_management.cash_session (organization_id, id) ON DELETE RESTRICT,
    CONSTRAINT payment_refund_status_check CHECK (status IN ('CREATED', 'CONFIRMED')),
    CONSTRAINT payment_refund_amount_check CHECK (amount > 0),
    CONSTRAINT payment_refund_confirmation_check CHECK (
        (status = 'CREATED' AND confirmed_at IS NULL AND version = 1)
        OR (status = 'CONFIRMED' AND confirmed_at IS NOT NULL AND version = 2)
    )
)
SQL);
        $this->addSql('CREATE UNIQUE INDEX payment_refund_idempotency_unique ON payments.payment_refund (organization_id, payment_id, idempotency_key)');
        $this->addSql('CREATE INDEX payment_refund_payment_idx ON payments.payment_refund (organization_id, payment_id, status)');
        $this->addSql('CREATE INDEX payment_refund_return_idx ON payments.payment_refund (organization_id, return_sale_id, status)');
        $this->addSql('GRANT SELECT, INSERT ON payments.payment_refund TO zandu_runtime');
        $policy = "organization_id = NULLIF(current_setting('app.organization_id', true), '')::uuid";
        $this->addSql('ALTER TABLE payments.payment_refund ENABLE ROW LEVEL SECURITY');
        $this->addSql('ALTER TABLE payments.payment_refund FORCE ROW LEVEL SECURITY');
        $this->addSql("CREATE POLICY payment_refund_tenant_isolation ON payments.payment_refund FOR ALL TO zandu_runtime USING ($policy) WITH CHECK ($policy)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE payments.payment_refund');
    }
}
