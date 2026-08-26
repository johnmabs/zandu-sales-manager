<?php

declare(strict_types=1);

namespace Zandu\Modules\Pricing\Presentation\Api;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;

#[ApiResource(operations: [new GetCollection(name: 'price_list_list', uriTemplate: '/price-lists', provider: PriceListProvider::class),new Get(name: 'price_list_get', uriTemplate: '/price-lists/{id}', provider: PriceListProvider::class)])]
final readonly class PriceListResource
{
    public function __construct(public string $id, public string $organizationId, public string $code, public string $name, public string $currency, public string $status, public string $scope, public ?string $validFrom, public ?string $validTo, public int $priority, public string $createdAt, public int $version) {}
}
