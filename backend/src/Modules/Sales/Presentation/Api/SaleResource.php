<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Presentation\Api;

use ApiPlatform\Metadata\{ApiResource,Delete,Get,Link,Patch,Post};

#[ApiResource(operations: [
    new Post(name: 'sale_create', uriTemplate: '/stores/{storeId}/sales', uriVariables: ['storeId' => new Link(fromClass: self::class, identifiers: ['id'])], read: false, input: false, processor: SaleProcessor::class),
    new Get(name: 'sale_get', uriTemplate: '/sales/{id}', provider: SaleProvider::class),
    new Post(name: 'sale_line_add', uriTemplate: '/sales/{id}/lines', read: false, input: SaleLineInput::class, processor: SaleProcessor::class),
    new Patch(name: 'sale_line_update', uriTemplate: '/sales/{id}/lines/{lineId}', uriVariables: ['id' => new Link(fromClass: self::class, identifiers: ['id']), 'lineId' => new Link(fromClass: self::class, identifiers: ['id'])], read: false, input: UpdateSaleLineInput::class, processor: SaleProcessor::class),
    new Delete(name: 'sale_line_remove', uriTemplate: '/sales/{id}/lines/{lineId}', uriVariables: ['id' => new Link(fromClass: self::class, identifiers: ['id']), 'lineId' => new Link(fromClass: self::class, identifiers: ['id'])], read: false, processor: SaleProcessor::class),
    new Post(name: 'sale_cancel', uriTemplate: '/sales/{id}/cancel', read: false, input: false, processor: SaleProcessor::class),
    new Post(name: 'sale_complete', uriTemplate: '/sales/{id}/complete', read: false, input: CompleteSaleInput::class, output: CompleteSaleResultResource::class, processor: SaleProcessor::class),
    new Get(name: 'sale_receipt', uriTemplate: '/sales/{id}/receipt', provider: SaleProvider::class),
])]
final readonly class SaleResource
{
    /** @param list<array<string,mixed>> $lines */
    public function __construct(public string $id, public string $storeId, public string $status, public string $currency, public array $lines, public string $subtotal, public string $discountTotal, public string $taxTotal, public string $total, public ?string $businessDate, public ?string $completedAt, public int $version) {}
}
