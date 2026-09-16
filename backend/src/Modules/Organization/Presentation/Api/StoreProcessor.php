<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Presentation\Api;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use InvalidArgumentException;
use Zandu\Modules\Organization\Application\CancelStoreClosure\CancelStoreClosure;
use Zandu\Modules\Organization\Application\CancelStoreClosure\CancelStoreClosureHandler;
use Zandu\Modules\Organization\Application\CreateStore\CreateStore;
use Zandu\Modules\Organization\Application\CreateStore\CreateStoreHandler;
use Zandu\Modules\Organization\Application\ReactivateStore\ReactivateStore;
use Zandu\Modules\Organization\Application\ReactivateStore\ReactivateStoreHandler;
use Zandu\Modules\Organization\Application\RequestStoreClosure\RequestStoreClosure;
use Zandu\Modules\Organization\Application\RequestStoreClosure\RequestStoreClosureHandler;
use Zandu\Modules\Organization\Application\StoreClosureViewFactory;
use Zandu\Modules\Organization\Application\StoreViewFactory;
use Zandu\Modules\Organization\Application\SuspendStore\SuspendStore;
use Zandu\Modules\Organization\Application\SuspendStore\SuspendStoreHandler;
use Zandu\Modules\Organization\Application\UpdateStore\UpdateStore;
use Zandu\Modules\Organization\Application\UpdateStore\UpdateStoreHandler;
use Zandu\SharedKernel\Context\CurrentActorProvider;
use Zandu\SharedKernel\Identity\StoreId;
use Zandu\SharedKernel\Identity\UuidFactory;
use Zandu\SharedKernel\Versioning\ExpectedVersion;

/** @implements ProcessorInterface<mixed, StoreResource|StoreClosureResource> */
final readonly class StoreProcessor implements ProcessorInterface
{
    public function __construct(
        private CurrentActorProvider $actors,
        private UuidFactory $uuidFactory,
        private StoreViewFactory $views,
        private StoreClosureViewFactory $closureViews,
        private StoreResourceFactory $resources,
        private CreateStoreHandler $create,
        private UpdateStoreHandler $update,
        private SuspendStoreHandler $suspend,
        private ReactivateStoreHandler $reactivate,
        private RequestStoreClosureHandler $requestClosure,
        private CancelStoreClosureHandler $cancelClosure,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): StoreResource|StoreClosureResource
    {
        $actor = $this->actors->resolve();
        $name = $operation->getName();
        if ('store_create' === $name) {
            $input = $data instanceof StoreCreateInput ? $data : throw new InvalidArgumentException('Store creation input is required.');
            $store = ($this->create)(new CreateStore($input->code, $input->name, $input->address, $input->timeZone, $input->currency, $input->locale, $actor));

            return $this->resources->fromView($this->views->fromAggregate($store));
        }

        $id = StoreId::fromString($this->id($uriVariables), $this->uuidFactory);
        if ('store_update' === $name) {
            $input = $data instanceof StoreUpdateInput ? $data : throw new InvalidArgumentException('Store update input is required.');
            $store = ($this->update)(new UpdateStore($id, $input->name, $input->address, $input->timeZone, $input->locale, ExpectedVersion::fromInt($input->expectedVersion), $actor));

            return $this->resources->fromView($this->views->fromAggregate($store));
        }
        if ('store_closure_request' === $name) {
            $input = $data instanceof StoreClosureRequestInput ? $data : throw new InvalidArgumentException('Store closure input is required.');
            $closure = ($this->requestClosure)(new RequestStoreClosure($id, $input->reason, $actor));

            return $this->resources->closureFromView($this->closureViews->fromAggregate($closure));
        }

        $store = match ($name) {
            'store_suspend' => ($this->suspend)(new SuspendStore($id, $actor)),
            'store_reactivate' => ($this->reactivate)(new ReactivateStore($id, $actor)),
            'store_closure_cancel' => ($this->cancelClosure)(new CancelStoreClosure($id, $actor)),
            default => throw new InvalidArgumentException('Unsupported store operation.'),
        };

        return $this->resources->fromView($this->views->fromAggregate($store));
    }

    /** @param array<string,mixed> $uriVariables */
    private function id(array $uriVariables): string
    {
        $id = $uriVariables['id'] ?? null;

        return is_string($id) ? $id : throw new InvalidArgumentException('Store identifier is required.');
    }
}
