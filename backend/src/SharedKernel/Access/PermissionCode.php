<?php

declare(strict_types=1);

namespace Zandu\SharedKernel\Access;

enum PermissionCode: string
{
    case OrganizationRead = 'ORGANIZATION_READ';
    case OrganizationUpdate = 'ORGANIZATION_UPDATE';
    case OrganizationSuspend = 'ORGANIZATION_SUSPEND';

    case StoreCreate = 'STORE_CREATE';
    case StoreRead = 'STORE_READ';
    case StoreUpdate = 'STORE_UPDATE';
    case StoreSuspend = 'STORE_SUSPEND';
    case StoreClose = 'STORE_CLOSE';

    case MemberInvite = 'MEMBER_INVITE';
    case MemberRead = 'MEMBER_READ';
    case MemberSuspend = 'MEMBER_SUSPEND';
    case MemberRevoke = 'MEMBER_REVOKE';

    case RoleRead = 'ROLE_READ';
    case RoleAssign = 'ROLE_ASSIGN';
    case RoleRevoke = 'ROLE_REVOKE';

    case SecurityAuditRead = 'SECURITY_AUDIT_READ';

    case ProductCreate = 'PRODUCT_CREATE';
    case ProductUpdate = 'PRODUCT_UPDATE';
}
