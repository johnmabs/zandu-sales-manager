<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Presentation\Api;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;

#[ApiResource(operations: [
    new Post(name: 'member_invitation_create', uriTemplate: '/member-invitations', input: CreateInvitationInput::class, output: CreatedInvitationResource::class, processor: InvitationProcessor::class),
    new Post(name: 'member_invitation_cancel', uriTemplate: '/member-invitations/{id}/cancel', read: false, input: false, processor: InvitationProcessor::class),
    new Post(name: 'invitation_accept', uriTemplate: '/invitations/{token}/accept', uriVariables: [
        'token' => new Link(fromClass: self::class, identifiers: ['id']),
    ], read: false, input: false, output: AcceptedInvitationResource::class, processor: InvitationProcessor::class),
])]
final readonly class InvitationResource
{
    /** @param non-empty-list<array{roleCode: string, storeIds: list<string>}> $roleAssignments */
    public function __construct(
        public string $id,
        public string $organizationId,
        public string $email,
        public string $status,
        public string $expiresAt,
        public array $roleAssignments,
        public ?string $acceptedAt,
        public int $version,
    ) {}
}
