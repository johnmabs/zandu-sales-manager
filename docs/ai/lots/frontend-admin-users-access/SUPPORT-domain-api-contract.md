# F2 — Support modèle et contrat API

## Concepts

`User ≠ OrganizationMembership ≠ Role ≠ RoleAssignment ≠ AccessScope`. L’Admin gère les membres de l’organisation, jamais les utilisateurs globaux. Un membership suit `INVITED → ACTIVE → SUSPENDED → REVOKED`; seul `ACTIVE` accorde des permissions.

Les assignments référencent un rôle, un scope `ORGANIZATION` ou `SELECTED_STORES`, l’auteur/date et éventuellement `expiresAt`. Les rôles système `ORGANIZATION_OWNER`, `STORE_MANAGER`, `CASHIER`, `ACCOUNTANT` sont globaux et immuables.

## Endpoints disponibles

- `GET /api/members`, `GET /api/members/{id}` et transitions Suspend/Reactivate/Revoke exposées par l’OpenAPI réel.
- `GET /api/roles`.
- `POST /api/members/{id}/role-assignments`.
- `DELETE /api/members/{id}/role-assignments/{assignmentId}`.
- `POST /api/member-invitations` et `POST /api/member-invitations/{id}/cancel`.
- Lecture des invitations seulement si le contrat courant expose l’opération.

Ne pas inventer resend, extend, edit invitation ou CRUD de rôles custom. Les réponses membre restent tenant-scoped et n’exposent aucune donnée d’authentification globale.
