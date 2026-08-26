<?php
declare(strict_types=1);
namespace Zandu\Modules\Inventory\Domain\StockMovement;
use InvalidArgumentException;
use Zandu\SharedKernel\Identity\Uuid;
final readonly class StockMovementSource
{
    private function __construct(private string $type, private ?Uuid $referenceId) {}
    public static function initialization(): self { return new self('INITIALIZATION', null); }
    public static function manualAdjustment(?Uuid $referenceId = null): self { return new self('MANUAL_ADJUSTMENT', $referenceId); }
    public function type(): string { return $this->type; }
    public function referenceId(): ?Uuid { return $this->referenceId; }
}
