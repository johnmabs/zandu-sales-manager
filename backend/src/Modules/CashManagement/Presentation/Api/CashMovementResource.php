<?php

declare(strict_types=1);

namespace Zandu\Modules\CashManagement\Presentation\Api;

use ApiPlatform\Metadata\{ApiResource,GetCollection,Link,Post};

#[ApiResource(operations: [new GetCollection(name: 'cash_movement_list', uriTemplate: '/stores/{storeId}/cash-sessions/{sessionId}/movements', uriVariables: ['storeId' => new Link(fromClass: self::class, identifiers: ['id']),'sessionId' => new Link(fromClass: self::class, identifiers: ['id'])], provider: CashMovementProvider::class),new Post(name: 'cash_in_record', uriTemplate: '/stores/{storeId}/cash-sessions/{sessionId}/cash-in', uriVariables: ['storeId' => new Link(fromClass: self::class, identifiers: ['id']),'sessionId' => new Link(fromClass: self::class, identifiers: ['id'])], read: false, input: CashMovementInput::class, processor: CashMovementProcessor::class),new Post(name: 'cash_out_record', uriTemplate: '/stores/{storeId}/cash-sessions/{sessionId}/cash-out', uriVariables: ['storeId' => new Link(fromClass: self::class, identifiers: ['id']),'sessionId' => new Link(fromClass: self::class, identifiers: ['id'])], read: false, input: CashMovementInput::class, processor: CashMovementProcessor::class),new Post(name: 'cash_withdrawal_record', uriTemplate: '/stores/{storeId}/cash-sessions/{sessionId}/withdrawals', uriVariables: ['storeId' => new Link(fromClass: self::class, identifiers: ['id']),'sessionId' => new Link(fromClass: self::class, identifiers: ['id'])], read: false, input: CashMovementInput::class, processor: CashMovementProcessor::class)])]
final readonly class CashMovementResource
{
    public function __construct(public string $id, public string $storeId, public string $sessionId, public string $type, public string $amount, public string $currency, public ?string $reason, public ?string $sourceReference, public string $occurredAt) {}
}
