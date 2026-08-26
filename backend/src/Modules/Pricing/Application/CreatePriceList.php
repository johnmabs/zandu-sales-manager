<?php

declare(strict_types=1);

namespace Zandu\Modules\Pricing\Application;

use DateTimeImmutable;
use Zandu\SharedKernel\Context\ActorContext;

final readonly class CreatePriceList
{
    public function __construct(public string $code, public string $name, public string $currency, public ?DateTimeImmutable $validFrom, public ?DateTimeImmutable $validTo, public int $priority, public ActorContext $actorContext) {}
}
