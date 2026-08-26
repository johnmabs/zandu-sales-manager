<?php

declare(strict_types=1);

namespace Zandu\Modules\Pricing\Application;

final readonly class PriceListView
{
    public function __construct(public string $id, public string $organizationId, public string $code, public string $name, public string $currency, public string $status, public string $scope, public ?string $validFrom, public ?string $validTo, public int $priority, public string $createdAt, public int $version) {}
}
