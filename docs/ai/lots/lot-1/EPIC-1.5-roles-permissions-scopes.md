# 9. Epic 1.5 — Roles, permissions & scopes

## Objectif

Construire une authorization métier basée sur les permissions atomiques et le périmètre.

---

## Étape 1.5.1 — Permission catalog initial

Introduire uniquement les permissions nécessaires au Lot 1.

Exemple initial :

```text
ORGANIZATION_READ
ORGANIZATION_UPDATE
ORGANIZATION_SUSPEND

STORE_CREATE
STORE_READ
STORE_UPDATE
STORE_SUSPEND
STORE_CLOSE

MEMBER_INVITE
MEMBER_READ
MEMBER_SUSPEND
MEMBER_REVOKE

ROLE_READ
ROLE_ASSIGN
ROLE_REVOKE

SECURITY_AUDIT_READ
```

Ne pas créer prématurément les permissions Sales/Inventory/Cash si aucun use case ne les utilise encore.

### Commit proposé

```text
feat(access): add administration permission catalog
```

---

## Étape 1.5.2 — Ajouter `Role`

Modèle :

```text
Role
├── RoleId
├── OrganizationId?
├── RoleCode
├── RoleType
├── RoleStatus
├── name
├── description?
├── Set<PermissionCode>
└── Version
```

Règles :

- `OrganizationId = null` possible pour un rôle `SYSTEM` ;
- les rôles `SYSTEM` ne sont ni supprimables ni personnalisables ;
- les rôles `CUSTOM` appartiennent à une organization ;
- un rôle `ARCHIVED` n’accorde aucune permission.

### Commit proposé

```text
feat(access): add role aggregate
```

---

## Étape 1.5.3 — Ajouter les rôles système MVP

Créer au minimum :

```text
ORGANIZATION_OWNER
STORE_MANAGER
CASHIER
ACCOUNTANT
```

Le Lot 1 ne donne des permissions réelles que pour les capacités déjà implémentées.

Les permissions métier futures seront ajoutées avec les lots concernés.

### Commit proposé

```text
feat(access): seed system roles
```

---

## Étape 1.5.4 — Ajouter `AccessScope`

Scopes :

```text
ORGANIZATION
SELECTED_STORES
```

Pour `SELECTED_STORES`, toutes les `StoreId` doivent appartenir à la même `Organization`.

### Commit proposé

```text
feat(access): add organization and store access scopes
```

---

## Étape 1.5.5 — Ajouter `RoleAssignment`

Modèle :

```text
RoleAssignment
├── RoleId
├── AccessScope
├── assignedBy
├── assignedAt
└── expiresAt?
```

Règles :

- attribution expirée = aucun droit ;
- rôle archivé = aucun droit ;
- absence d’ALLOW = refus ;
- pas de permission négative dans le MVP.

### Commit proposé

```text
feat(access): add role assignments
```

---

## Étape 1.5.6 — Invariant du dernier owner

Invariant absolu :

```text
active ORGANIZATION_OWNER count >= 1
```

Les opérations suivantes doivent le protéger :

```text
SuspendOrganizationMembership
RevokeOrganizationMembership
RemoveRoleAssignment
ArchiveRole
```

Seul un owner actif peut attribuer ou retirer `ORGANIZATION_OWNER`.

L’auto-élévation silencieuse est interdite.

### Commit proposé

```text
feat(access): protect last organization owner
```

---

## Definition of Done — Epic 1.5

- permission catalog minimal ;
- rôles système disponibles ;
- scopes organisation/store fonctionnels ;
- expiration prise en compte ;
- invariant dernier owner prouvé par tests négatifs ;
- aucune logique applicative fondée sur une comparaison brute de rôle.

---
