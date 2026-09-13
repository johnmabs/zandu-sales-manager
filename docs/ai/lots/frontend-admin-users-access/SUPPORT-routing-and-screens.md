# F2 — Support routing et écrans

## Routes proposées

```text
/admin/access
/admin/access/members
/admin/access/members/{memberId}
/admin/access/invitations
/admin/access/invite
/admin/access/roles
```

Les conventions réelles du routeur Admin prévalent ; `/admin/access` peut rediriger vers Members.

## Minimum

`MemberListPage`, `MemberDetailsPage`, `InvitationListPage`, `InviteMemberPage`, `RoleCatalogPage`, puis dialogues Assign/Remove Role, Suspend/Reactivate/Revoke Member, Cancel Invitation et `StoreScopeSelector`.
