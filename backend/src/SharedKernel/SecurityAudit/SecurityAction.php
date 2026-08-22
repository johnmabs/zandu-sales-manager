<?php

declare(strict_types=1);

namespace Zandu\SharedKernel\SecurityAudit;

enum SecurityAction: string
{
    case OrganizationCreated = 'ORGANIZATION_CREATED';
    case OrganizationUpdated = 'ORGANIZATION_UPDATED';
    case OrganizationSuspended = 'ORGANIZATION_SUSPENDED';
    case OrganizationReactivated = 'ORGANIZATION_REACTIVATED';
    case StoreCreated = 'STORE_CREATED';
    case StoreUpdated = 'STORE_UPDATED';
    case StoreSuspended = 'STORE_SUSPENDED';
    case StoreReactivated = 'STORE_REACTIVATED';
    case MemberInvited = 'MEMBER_INVITED';
    case MemberSuspended = 'MEMBER_SUSPENDED';
    case MemberReactivated = 'MEMBER_REACTIVATED';
    case MemberRevoked = 'MEMBER_REVOKED';
    case RoleAssigned = 'ROLE_ASSIGNED';
    case RoleRemoved = 'ROLE_REMOVED';
    case OwnerAssigned = 'OWNER_ASSIGNED';
    case OwnerRemoved = 'OWNER_REMOVED';
    case AuthorizationDenied = 'AUTHORIZATION_DENIED';
}
