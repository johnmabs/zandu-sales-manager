<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Domain;

use LogicException;
use Zandu\SharedKernel\Error\CodedDomainException;

final class InventoryRuleViolation extends LogicException implements CodedDomainException
{
    private function __construct(private readonly string $errorCode, private readonly string $publicMessage)
    {
        parent::__construct($publicMessage);
    }

    public static function with(string $code, string $message): self
    {
        return new self($code, $message);
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }

    public function publicMessage(): string
    {
        return $this->publicMessage;
    }
}
