<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Domain\GoodsReceiptCorrection;

use DateTimeImmutable;
use DateTimeZone;
use Zandu\Modules\Purchasing\Domain\PurchasingRuleViolation;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\GoodsReceiptCorrectionId;
use Zandu\SharedKernel\Identity\GoodsReceiptId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Versioning\{TracksAggregateVersion, VersionedAggregate};

final class GoodsReceiptCorrection implements VersionedAggregate
{
    use TracksAggregateVersion;

    /** @param list<GoodsReceiptCorrectionLine> $lines */
    private function __construct(
        private readonly GoodsReceiptCorrectionId $id,
        private readonly OrganizationId $organizationId,
        private readonly GoodsReceiptId $goodsReceiptId,
        private readonly string $reason,
        private GoodsReceiptCorrectionStatus $status,
        private readonly ActorId $createdBy,
        private readonly DateTimeImmutable $createdAt,
        private ?ActorId $postedBy,
        private ?DateTimeImmutable $postedAt,
        private int $version,
        private array $lines,
    ) {
        $this->assertValidVersion();
    }

    public static function create(GoodsReceiptCorrectionId $id, OrganizationId $organizationId, GoodsReceiptId $goodsReceiptId, string $reason, ActorId $createdBy, DateTimeImmutable $createdAt): self
    {
        return new self($id, $organizationId, $goodsReceiptId, self::normalizeReason($reason), GoodsReceiptCorrectionStatus::Draft, $createdBy, self::utc($createdAt), null, null, 1, []);
    }

    /** @param list<GoodsReceiptCorrectionLine> $lines */
    public static function reconstitute(GoodsReceiptCorrectionId $id, OrganizationId $organizationId, GoodsReceiptId $goodsReceiptId, string $reason, GoodsReceiptCorrectionStatus $status, ActorId $createdBy, DateTimeImmutable $createdAt, ?ActorId $postedBy, ?DateTimeImmutable $postedAt, int $version, array $lines): self
    {
        return new self($id, $organizationId, $goodsReceiptId, self::normalizeReason($reason), $status, $createdBy, self::utc($createdAt), $postedBy, null === $postedAt ? null : self::utc($postedAt), $version, $lines);
    }

    public function addLine(GoodsReceiptCorrectionLine $line): void
    {
        $this->ensureDraft();
        if (!$line->correctionId()->equals($this->id)) {
            throw PurchasingRuleViolation::with('GOODS_RECEIPT_CORRECTION_LINE_MISMATCH', 'Correction line belongs to another correction.');
        }
        foreach ($this->lines as $existing) {
            if ($existing->productId()->equals($line->productId())) {
                throw PurchasingRuleViolation::with('GOODS_RECEIPT_CORRECTION_PRODUCT_DUPLICATE', 'A product can occur only once in a correction.');
            }
        }
        $this->lines[] = $line;
        $this->advanceVersion();
    }

    public function post(ActorId $actorId, DateTimeImmutable $postedAt): void
    {
        $this->ensureDraft();
        if ([] === $this->lines) {
            throw PurchasingRuleViolation::with('GOODS_RECEIPT_CORRECTION_EMPTY', 'A correction must contain at least one line.');
        }
        $this->status = GoodsReceiptCorrectionStatus::Posted;
        $this->postedBy = $actorId;
        $this->postedAt = self::utc($postedAt);
        $this->advanceVersion();
    }

    private function ensureDraft(): void
    {
        if (GoodsReceiptCorrectionStatus::Draft !== $this->status) {
            throw PurchasingRuleViolation::with('GOODS_RECEIPT_CORRECTION_ALREADY_POSTED', 'A posted correction is immutable.');
        }
    }

    private static function normalizeReason(string $reason): string
    {
        $reason = trim($reason);
        if ('' === $reason) {
            throw PurchasingRuleViolation::with('GOODS_RECEIPT_CORRECTION_REASON_REQUIRED', 'A correction requires a reason.');
        }
        if (mb_strlen($reason) > 500) {
            throw PurchasingRuleViolation::with('GOODS_RECEIPT_CORRECTION_REASON_INVALID', 'Correction reason cannot exceed 500 characters.');
        }
        return $reason;
    }

    private static function utc(DateTimeImmutable $at): DateTimeImmutable
    {
        return $at->setTimezone(new DateTimeZone('UTC'));
    }

    public function id(): GoodsReceiptCorrectionId
    {
        return $this->id;
    }
    public function organizationId(): OrganizationId
    {
        return $this->organizationId;
    }
    public function goodsReceiptId(): GoodsReceiptId
    {
        return $this->goodsReceiptId;
    }
    public function reason(): string
    {
        return $this->reason;
    }
    public function status(): GoodsReceiptCorrectionStatus
    {
        return $this->status;
    }
    public function createdBy(): ActorId
    {
        return $this->createdBy;
    }
    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
    public function postedBy(): ?ActorId
    {
        return $this->postedBy;
    }
    public function postedAt(): ?DateTimeImmutable
    {
        return $this->postedAt;
    }
    /** @return list<GoodsReceiptCorrectionLine> */ public function lines(): array
    {
        return $this->lines;
    }
}
