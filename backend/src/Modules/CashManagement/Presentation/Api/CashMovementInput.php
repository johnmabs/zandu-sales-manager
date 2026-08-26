<?php
declare(strict_types=1);
namespace Zandu\Modules\CashManagement\Presentation\Api;
final readonly class CashMovementInput{public function __construct(public string $amount,public string $currency,public string $reason,public ?string $sourceReference=null){} }
