# 8. Epic 1.4 — Membership lifecycle

## Objectif

Matérialiser l’accès d’un `User` à une `Organization`.

---

## Étape 1.4.1 — Ajouter `OrganizationMembership`

Modèle :

```text
OrganizationMembership
├── OrganizationMembershipId
├── OrganizationId
├── UserId
├── MembershipStatus
├── roleAssignments
├── authorizationVersion
├── lifecycle actors and timestamps
└── Version
```

Contrainte :

```text
UNIQUE (organization_id, user_id)
```

Cycle :

```text
INVITED
→ ACTIVE
→ SUSPENDED
→ REVOKED
```

Règles :

- seul `ACTIVE` accorde des permissions ;
- `SUSPENDED` est réversible ;
- `REVOKED` conserve les références historiques ;
- une révocation ne supprime jamais l’acteur des historiques métier.

### Commit proposé

```text
feat(identity): add organization membership aggregate
```

---

## Étape 1.4.2 — Persistence Membership

Ajouter :

- mapping Doctrine ;
- migration ;
- repository ;
- contrainte unique ;
- tests PostgreSQL.

### Commit proposé

```text
feat(identity): persist organization memberships
```

---

## Étape 1.4.3 — Lifecycle Membership

Créer :

```text
SuspendOrganizationMembership
ReactivateOrganizationMembership
RevokeOrganizationMembership
```

Chaque modification d’accès doit incrémenter :

```text
authorizationVersion
```

### Commits proposés

```text
feat(identity): add membership suspension lifecycle
feat(identity): add membership revocation
```

---

## Étape 1.4.4 — Révocation effective

Les tokens/caches portant une `authorizationVersion` obsolète doivent être invalidés ou recalculés selon la stratégie établie au Lot 0.

Les opérations sensibles exigent une vérification serveur fraîche.

### Commit proposé

```text
feat(auth): enforce membership authorization version
```

---

## Definition of Done — Epic 1.4

- lifecycle complet testé ;
- révocation immédiate effective ;
- références historiques préservées ;
- `authorizationVersion` correctement incrémentée ;
- tenant isolation vérifiée.

---
