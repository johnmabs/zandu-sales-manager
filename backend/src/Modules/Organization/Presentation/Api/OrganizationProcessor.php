<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Presentation\Api;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use InvalidArgumentException;
use Zandu\Modules\Organization\Application\ReactivateOrganization\ReactivateOrganization;
use Zandu\Modules\Organization\Application\ReactivateOrganization\ReactivateOrganizationHandler;
use Zandu\Modules\Organization\Application\RequestOrganizationClosure\RequestOrganizationClosure;
use Zandu\Modules\Organization\Application\RequestOrganizationClosure\RequestOrganizationClosureHandler;
use Zandu\Modules\Organization\Application\SuspendOrganization\SuspendOrganization;
use Zandu\Modules\Organization\Application\SuspendOrganization\SuspendOrganizationHandler;
use Zandu\Modules\Organization\Application\UpdateOrganization\UpdateOrganization;
use Zandu\Modules\Organization\Application\UpdateOrganization\UpdateOrganizationHandler;
use Zandu\Platform\Auth\Context\ActorContextResolver;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\UuidFactory;

/** @implements ProcessorInterface<mixed, OrganizationResource> */
final readonly class OrganizationProcessor implements ProcessorInterface
{
    public function __construct(
        private ActorContextResolver $actors,
        private UuidFactory $uuidFactory,
        private OrganizationResourceFactory $resources,
        private UpdateOrganizationHandler $update,
        private SuspendOrganizationHandler $suspend,
        private ReactivateOrganizationHandler $reactivate,
        private RequestOrganizationClosureHandler $requestClosure,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): OrganizationResource
    {
        $actor = $this->actors->resolve();
        $name = $operation->getName();
        $id = OrganizationId::fromString($this->id($uriVariables), $this->uuidFactory);
        if ('organization_update' === $name) {
            $input = $this->input($data);
            $organization = ($this->update)(new UpdateOrganization($id, $input->name, $input->countryCode, $input->defaultCurrency, $input->defaultTimeZone, $input->defaultLocale, $actor));

            return $this->resources->fromAggregate($organization);
        }
        $organization = match ($name) {
            'organization_suspend' => ($this->suspend)(new SuspendOrganization($id, $actor)),
            'organization_reactivate' => ($this->reactivate)(new ReactivateOrganization($id, $actor)),
            'organization_closure_request' => ($this->requestClosure)(new RequestOrganizationClosure($id, $actor)),
            default => throw new InvalidArgumentException('Unsupported organization operation.'),
        };

        return $this->resources->fromAggregate($organization);
    }

    private function input(mixed $data): OrganizationInput
    {
        return $data instanceof OrganizationInput ? $data : throw new InvalidArgumentException('Organization input is required.');
    }

    /** @param array<string,mixed> $uriVariables */
    private function id(array $uriVariables): string
    {
        $id = $uriVariables['id'] ?? null;

        return is_string($id) ? $id : throw new InvalidArgumentException('Organization identifier is required.');
    }
}
