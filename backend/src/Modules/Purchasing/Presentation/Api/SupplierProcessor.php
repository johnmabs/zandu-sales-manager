<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Presentation\Api;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use InvalidArgumentException;
use Zandu\Modules\Purchasing\Application\ActivateSupplier\{ActivateSupplier, ActivateSupplierHandler};
use Zandu\Modules\Purchasing\Application\ArchiveSupplier\{ArchiveSupplier, ArchiveSupplierHandler};
use Zandu\Modules\Purchasing\Application\CreateSupplier\{CreateSupplier, CreateSupplierHandler};
use Zandu\Modules\Purchasing\Application\DeactivateSupplier\{DeactivateSupplier, DeactivateSupplierHandler};
use Zandu\Modules\Purchasing\Application\SupplierViewFactory;
use Zandu\Modules\Purchasing\Application\UpdateSupplier\{UpdateSupplier, UpdateSupplierHandler};
use Zandu\SharedKernel\Context\CurrentActorProvider;
use Zandu\SharedKernel\Identity\{SupplierId, UuidFactory};

/** @implements ProcessorInterface<mixed, SupplierResource> */
final readonly class SupplierProcessor implements ProcessorInterface
{
    public function __construct(private CurrentActorProvider $actors, private UuidFactory $uuids, private SupplierViewFactory $views, private SupplierResourceMapper $mapper, private CreateSupplierHandler $create, private UpdateSupplierHandler $update, private ActivateSupplierHandler $activate, private DeactivateSupplierHandler $deactivate, private ArchiveSupplierHandler $archive) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): SupplierResource
    {
        $actor = $this->actors->resolve();
        if ('supplier_create' === $operation->getName() && $data instanceof SupplierInput) {
            return $this->mapper->map($this->views->create(($this->create)(new CreateSupplier($data->name, $data->phone, $data->email, $data->address, $data->notes, $actor))));
        }
        $rawId = $uriVariables['id'] ?? null;
        if (!is_string($rawId)) {
            throw new InvalidArgumentException('Supplier identifier is required.');
        }
        $id = SupplierId::fromString($rawId, $this->uuids);
        if ('supplier_update' === $operation->getName() && $data instanceof SupplierInput) {
            return $this->mapper->map($this->views->create(($this->update)(new UpdateSupplier($id, $data->name, $data->phone, $data->email, $data->address, $data->notes, $actor))));
        }
        $supplier = match ($operation->getName()) {
            'supplier_activate' => ($this->activate)(new ActivateSupplier($id, $actor)),
            'supplier_deactivate' => ($this->deactivate)(new DeactivateSupplier($id, $actor)),
            'supplier_archive' => ($this->archive)(new ArchiveSupplier($id, $actor)),
            default => throw new InvalidArgumentException('Unsupported supplier operation or payload.'),
        };

        return $this->mapper->map($this->views->create($supplier));
    }
}
