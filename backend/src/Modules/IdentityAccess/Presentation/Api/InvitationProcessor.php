<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Presentation\Api;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use DateTimeImmutable;
use InvalidArgumentException;
use Zandu\Modules\IdentityAccess\Application\AcceptOrganizationInvitation\AcceptOrganizationInvitation;
use Zandu\Modules\IdentityAccess\Application\AcceptOrganizationInvitation\AcceptOrganizationInvitationHandler;
use Zandu\Modules\IdentityAccess\Application\CancelOrganizationInvitation\CancelOrganizationInvitation;
use Zandu\Modules\IdentityAccess\Application\CancelOrganizationInvitation\CancelOrganizationInvitationHandler;
use Zandu\Modules\IdentityAccess\Application\IntendedRoleAssignmentFactory;
use Zandu\Modules\IdentityAccess\Application\InvitationViewFactory;
use Zandu\Modules\IdentityAccess\Application\InviteOrganizationMember\InviteOrganizationMember;
use Zandu\Modules\IdentityAccess\Application\InviteOrganizationMember\InviteOrganizationMemberHandler;
use Zandu\SharedKernel\Context\CurrentActorProvider;
use Zandu\SharedKernel\Identity\OrganizationInvitationId;
use Zandu\SharedKernel\Identity\UuidFactory;

/** @implements ProcessorInterface<mixed, InvitationResource|CreatedInvitationResource|AcceptedInvitationResource> */
final readonly class InvitationProcessor implements ProcessorInterface
{
    public function __construct(
        private CurrentActorProvider $actors,
        private UuidFactory $uuidFactory,
        private IntendedRoleAssignmentFactory $assignments,
        private InvitationViewFactory $views,
        private InvitationResourceFactory $resources,
        private InviteOrganizationMemberHandler $invite,
        private CancelOrganizationInvitationHandler $cancel,
        private AcceptOrganizationInvitationHandler $accept,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): InvitationResource|CreatedInvitationResource|AcceptedInvitationResource
    {
        $actor = $this->actors->resolve();
        $name = $operation->getName();
        if ('member_invitation_create' === $name) {
            $input = $data instanceof CreateInvitationInput ? $data : throw new InvalidArgumentException('Invitation input is required.');
            $assignments = array_map(
                fn(IntendedRoleAssignmentInput $assignment) => $this->assignments->create($assignment->roleCode, $assignment->storeIds),
                $input->roleAssignments,
            );
            $created = ($this->invite)(new InviteOrganizationMember(
                $input->email,
                $assignments,
                null !== $input->expiresAt ? new DateTimeImmutable($input->expiresAt) : null,
                $actor,
            ));

            return $this->resources->fromCreated($this->views->fromCreated($created));
        }
        if ('member_invitation_cancel' === $name) {
            $id = OrganizationInvitationId::fromString($this->variable($uriVariables, 'id'), $this->uuidFactory);
            $invitation = ($this->cancel)(new CancelOrganizationInvitation($id, $actor));

            return $this->resources->fromView($this->views->fromAggregate($invitation));
        }
        if ('invitation_accept' === $name) {
            $membership = ($this->accept)(new AcceptOrganizationInvitation($this->variable($uriVariables, 'token'), $actor));

            return $this->resources->fromAccepted($this->views->fromMembership($membership));
        }

        throw new InvalidArgumentException('Unsupported invitation operation.');
    }

    /** @param array<string,mixed> $uriVariables */
    private function variable(array $uriVariables, string $name): string
    {
        $value = $uriVariables[$name] ?? null;

        return is_string($value) ? $value : throw new InvalidArgumentException(sprintf('Invitation %s is required.', $name));
    }
}
