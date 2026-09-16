<?php

declare(strict_types=1);

namespace Zandu\Modules\CashManagement\Presentation\Api;

use ApiPlatform\Metadata\{ApiResource,Get,GetCollection,Link,Patch,Post};

#[ApiResource(operations: [new GetCollection(name: 'cash_register_list', uriTemplate: '/stores/{storeId}/cash-registers', uriVariables: ['storeId' => new Link(fromClass: self::class, identifiers: ['id'])], provider: CashRegisterProvider::class),new Post(name: 'cash_register_create', uriTemplate: '/stores/{storeId}/cash-registers', uriVariables: ['storeId' => new Link(fromClass: self::class, identifiers: ['id'])], read: false, input: CashRegisterInput::class, processor: CashRegisterProcessor::class),new Get(name: 'cash_register_get', uriTemplate: '/stores/{storeId}/cash-registers/{id}', uriVariables: ['storeId' => new Link(fromClass: self::class, identifiers: ['id']),'id' => new Link(fromClass: self::class, identifiers: ['id'])], provider: CashRegisterProvider::class),new Patch(name: 'cash_register_update', uriTemplate: '/stores/{storeId}/cash-registers/{id}', uriVariables: ['storeId' => new Link(fromClass: self::class, identifiers: ['id']),'id' => new Link(fromClass: self::class, identifiers: ['id'])], read: false, input: CashRegisterUpdateInput::class, processor: CashRegisterProcessor::class),new Post(name: 'cash_register_activate', uriTemplate: '/stores/{storeId}/cash-registers/{id}/activate', uriVariables: ['storeId' => new Link(fromClass: self::class, identifiers: ['id']),'id' => new Link(fromClass: self::class, identifiers: ['id'])], read: false, input: false, processor: CashRegisterProcessor::class),new Post(name: 'cash_register_deactivate', uriTemplate: '/stores/{storeId}/cash-registers/{id}/deactivate', uriVariables: ['storeId' => new Link(fromClass: self::class, identifiers: ['id']),'id' => new Link(fromClass: self::class, identifiers: ['id'])], read: false, input: false, processor: CashRegisterProcessor::class),new Post(name: 'cash_register_archive', uriTemplate: '/stores/{storeId}/cash-registers/{id}/archive', uriVariables: ['storeId' => new Link(fromClass: self::class, identifiers: ['id']),'id' => new Link(fromClass: self::class, identifiers: ['id'])], read: false, input: false, processor: CashRegisterProcessor::class)])]
final readonly class CashRegisterResource
{
    public function __construct(public string $id, public string $organizationId, public string $storeId, public string $code, public string $name, public string $status, public int $version) {}
}
