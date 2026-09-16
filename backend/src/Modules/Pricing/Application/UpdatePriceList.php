<?php

declare(strict_types=1);

namespace Zandu\Modules\Pricing\Application;

use DateTimeImmutable;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\PriceListId;
use Zandu\SharedKernel\Versioning\ExpectedVersion;

final readonly class UpdatePriceList
{
    public function __construct(public PriceListId $priceListId, public string $code, public string $name, public ?DateTimeImmutable $validFrom, public ?DateTimeImmutable $validTo, public int $priority, public ExpectedVersion $expectedVersion, public ActorContext $actorContext) {}
}
