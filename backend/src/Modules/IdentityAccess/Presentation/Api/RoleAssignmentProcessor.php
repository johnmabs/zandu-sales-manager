<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Presentation\Api;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use InvalidArgumentException;
use Zandu\Modules\IdentityAccess\Application\RoleAssignment\RoleAssignmentApplicationService;
use Zandu\SharedKernel\Context\CurrentActorProvider;

/** @implements ProcessorInterface<mixed, MembershipResource> */
final readonly class RoleAssignmentProcessor implements ProcessorInterface
{
    public function __construct(
        private CurrentActorProvider $actors,
        private RoleAssignmentApplicationService $assignments,
        private MembershipResourceFactory $resources,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): MembershipResource
    {
        $membershipId = $this->variable($uriVariables, 'id');
        $actor = $this->actors->resolve();
        if ('member_role_assignment_create' === $operation->getName()) {
            $input = $data instanceof RoleAssignmentInput ? $data : throw new InvalidArgumentException('Role assignment input is required.');
            $view = $this->assignments->assign(
                $membershipId,
                $input->roleId,
                $input->scopeType,
                $input->storeIds,
                $input->expiresAt,
                $actor,
            );

            return $this->resources->fromView($view);
        }
        if ('member_role_assignment_delete' === $operation->getName()) {
            return $this->resources->fromView($this->assignments->remove(
                $membershipId,
                $this->variable($uriVariables, 'assignmentId'),
                $actor,
            ));
        }

        throw new InvalidArgumentException('Unsupported role assignment operation.');
    }

    /** @param array<string,mixed> $uriVariables */
    private function variable(array $uriVariables, string $name): string
    {
        $value = $uriVariables[$name] ?? null;

        return is_string($value) ? $value : throw new InvalidArgumentException(sprintf('%s is required.', $name));
    }
}
