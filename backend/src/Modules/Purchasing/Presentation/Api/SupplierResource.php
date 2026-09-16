<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Presentation\Api;

use ApiPlatform\Metadata\{ApiResource, Get, GetCollection, Patch, Post};

#[ApiResource(operations: [
    new GetCollection(name: 'supplier_list', uriTemplate: '/suppliers', provider: SupplierProvider::class),
    new Post(name: 'supplier_create', uriTemplate: '/suppliers', input: SupplierInput::class, processor: SupplierProcessor::class),
    new Get(name: 'supplier_get', uriTemplate: '/suppliers/{id}', provider: SupplierProvider::class),
    new Patch(name: 'supplier_update', uriTemplate: '/suppliers/{id}', read: false, input: SupplierUpdateInput::class, processor: SupplierProcessor::class),
    new Post(name: 'supplier_activate', uriTemplate: '/suppliers/{id}/activate', read: false, input: false, processor: SupplierProcessor::class),
    new Post(name: 'supplier_deactivate', uriTemplate: '/suppliers/{id}/deactivate', read: false, input: false, processor: SupplierProcessor::class),
    new Post(name: 'supplier_archive', uriTemplate: '/suppliers/{id}/archive', read: false, input: false, processor: SupplierProcessor::class),
])]
final readonly class SupplierResource
{
    public function __construct(public string $id, public string $name, public ?string $phone, public ?string $email, public ?string $address, public ?string $notes, public string $status, public string $createdAt, public ?string $updatedAt, public int $version) {}
}
