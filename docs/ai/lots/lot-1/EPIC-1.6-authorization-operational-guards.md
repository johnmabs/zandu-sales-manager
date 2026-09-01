# 10. Epic 1.6 — Authorization & operational guards

## Objectif

Faire respecter systématiquement permissions, scopes et statut opérationnel.

---

## Étape 1.6.1 — `AuthorizationService`

Contrat public :

```text
AuthorizationService
```

Responsabilité :

```text
authorize(
    ActorContext,
    PermissionCode,
    ResourceScope
)
```

Les handlers vérifient les permissions atomiques, jamais le nom du rôle.

### Commit proposé

```text
feat(access): add authorization service
```

---

## Étape 1.6.2 — Permission resolution

Résoudre :

```text
ActorContext
→ OrganizationMembership ACTIVE
→ valid RoleAssignments
→ active Roles
→ PermissionCodes
→ scope
```

Prendre en compte :

```text
authorizationVersion
role expiration
assignment expiration
role status
membership status
```

### Commit proposé

```text
feat(access): resolve effective permissions
```

---

## Étape 1.6.3 — Store scope enforcement

Scénario :

```text
permission = STORE_UPDATE
scope = SELECTED_STORES [A]

Store A → ALLOW
Store B → DENY
```

Les contrôles doivent être côté serveur.

### Commit proposé

```text
feat(access): enforce store scoped permissions
```

---

## Étape 1.6.4 — `OrganizationOperationalGuard`

Responsabilités :

```text
ACTIVE
→ nouvelles opérations autorisées selon permissions

SUSPENDED
→ nouvelles opérations refusées
→ remédiation/termination autorisée selon politique

CLOSED
→ nouvelles opérations refusées
```

### Commit proposé

```text
feat(organization): add operational guard
```

---

## Étape 1.6.5 — `StoreOperationalGuard`

Même logique pour `Store`.

Préparer la possibilité que certains use cases futurs soient explicitement autorisés en remédiation :

```text
CloseCashSession
ReceiveStockTransfer
FinalizeStockCount
```

sans les implémenter maintenant.

### Commit proposé

```text
feat(store): add operational guard
```

---

## Definition of Done — Epic 1.6

- toutes les commandes sensibles passent par authorization ;
- scopes testés ;
- organization suspendue bloque les nouvelles opérations ;
- store suspendu bloque les nouvelles opérations ;
- aucun rôle brut dans les handlers métier.

---
