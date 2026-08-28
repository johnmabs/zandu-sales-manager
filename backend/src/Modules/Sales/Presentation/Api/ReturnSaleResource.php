<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Presentation\Api;

use ApiPlatform\Metadata\{ApiResource, Get, GetCollection, Link, Post};

#[ApiResource(operations: [
    new Post(name: 'return_sale_create', uriTemplate: '/sales/{saleId}/returns', uriVariables: ['saleId' => new Link(fromClass: self::class, identifiers: ['id'])], read: false, input: ReturnSaleCreateInput::class, processor: ReturnSaleProcessor::class),
    new Post(name: 'return_sale_line_add', uriTemplate: '/returns/{id}/lines', read: false, input: ReturnSaleLineInput::class, processor: ReturnSaleProcessor::class),
    new Post(name: 'return_sale_complete', uriTemplate: '/returns/{id}/complete', read: false, input: false, processor: ReturnSaleProcessor::class),
    new Post(name: 'return_sale_cancel', uriTemplate: '/returns/{id}/cancel', read: false, input: false, processor: ReturnSaleProcessor::class),
    new Get(name: 'return_sale_get', uriTemplate: '/returns/{id}', provider: ReturnSaleProvider::class),
    new GetCollection(name: 'return_sale_list_by_sale', uriTemplate: '/sales/{saleId}/returns', uriVariables: ['saleId' => new Link(fromClass: self::class, identifiers: ['id'])], provider: ReturnSaleProvider::class),
])]
final readonly class ReturnSaleResource
{
    /** @param list<array<string, bool|string|array<string,string>|null>> $lines */
    public function __construct(public string $id, public string $saleId, public string $storeId, public string $status, public ?string $reason, public array $lines, public ?string $businessDate, public ?string $completedAt, public ?string $cancelledAt, public int $version) {}
}
