<?php

declare(strict_types=1);

namespace Zandu\Modules\CashManagement\Presentation\Api;

use ApiPlatform\Metadata\{ApiResource,Get,Link,Post};

#[ApiResource(operations: [new Post(name: 'cash_session_open', uriTemplate: '/stores/{storeId}/cash-registers/{cashRegisterId}/sessions/open', uriVariables: ['storeId' => new Link(fromClass: self::class, identifiers: ['id']),'cashRegisterId' => new Link(fromClass: self::class, identifiers: ['id'])], read: false, input: CashSessionOpenInput::class, processor: CashSessionProcessor::class),new Get(name: 'cash_session_get', uriTemplate: '/stores/{storeId}/cash-sessions/{id}', uriVariables: ['storeId' => new Link(fromClass: self::class, identifiers: ['id']),'id' => new Link(fromClass: self::class, identifiers: ['id'])], provider: CashSessionProvider::class),new Post(name: 'cash_session_close', uriTemplate: '/stores/{storeId}/cash-sessions/{id}/close', uriVariables: ['storeId' => new Link(fromClass: self::class, identifiers: ['id']),'id' => new Link(fromClass: self::class, identifiers: ['id'])], read: false, input: CashSessionCloseInput::class, processor: CashSessionProcessor::class)])]
final readonly class CashSessionResource
{
    public function __construct(public string $id, public string $storeId, public string $cashRegisterId, public string $status, public string $openingAmount, public string $currency, public ?string $countedClosingAmount, public ?string $expectedClosingAmount, public int $version) {}
}
