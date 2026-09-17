<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Presentation\Api;

use ApiPlatform\Metadata\{ApiResource, Get, GetCollection, Link, Post};

#[ApiResource(operations: [
    new GetCollection(name: 'stock_count_list', uriTemplate: '/stock-counts', provider: StockCountProvider::class, extraProperties: ['zandu_cursor_pagination' => true, 'zandu_cursor_direction' => 'desc']),
    new Post(name: 'stock_count_create', uriTemplate: '/stores/{storeId}/stock-counts', uriVariables: ['storeId' => new Link(fromClass: self::class, identifiers: ['id'])], read: false, input: StockCountCreateInput::class, processor: StockCountProcessor::class),
    new Get(name: 'stock_count_get', uriTemplate: '/stock-counts/{id}', provider: StockCountProvider::class),
    new Post(name: 'stock_count_start', uriTemplate: '/stock-counts/{id}/start', read: false, input: false, processor: StockCountProcessor::class),
    new Post(name: 'stock_count_record', uriTemplate: '/stock-counts/{id}/counts', read: false, input: StockCountRecordInput::class, processor: StockCountProcessor::class),
    new Post(name: 'stock_count_record_batch', uriTemplate: '/stock-counts/{id}/counts/batch', read: false, input: StockCountRecordBatchInput::class, processor: StockCountProcessor::class),
    new Post(name: 'stock_count_finalize', uriTemplate: '/stock-counts/{id}/finalization', read: false, input: StockCountFinalizationInput::class, processor: StockCountProcessor::class),
    new Post(name: 'stock_count_cancel', uriTemplate: '/stock-counts/{id}/cancel', read: false, input: false, processor: StockCountProcessor::class),
])]
final readonly class StockCountResource
{
    /**
     * @param list<string> $requestedProductIds
     * @param list<array<string, int|string|null>> $lines
     */
    public function __construct(
        public string $id,
        public string $storeId,
        public string $status,
        public string $mode,
        public string $scopeType,
        public array $requestedProductIds,
        public array $lines,
        public int $totalLineCount,
        public int $countedLineCount,
        public int $reconciledLineCount,
        public string $createdAt,
        public ?string $startedAt,
        public ?string $finalizationStartedAt,
        public ?string $completedAt,
        public ?string $cancelledAt,
        public int $version,
    ) {}
}
