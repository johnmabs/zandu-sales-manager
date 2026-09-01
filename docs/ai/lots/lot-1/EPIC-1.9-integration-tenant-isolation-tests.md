# 13. Epic 1.9 — Integration & tenant isolation tests

## Objectif

Prouver le comportement complet de l’administration opérationnelle.

---

## Étape 1.9.1 — Parcours Organization complet

Test réel :

```text
authenticated User
→ CreateOrganization
→ organization ACTIVE
→ owner membership created
→ owner role assigned
```

### Commit proposé

```text
test(organization): cover organization bootstrap workflow
```

---

## Étape 1.9.2 — Parcours multi-store

Test :

```text
Organization A
├── Store A1
└── Store A2
```

Vérifier :

- codes uniques dans le tenant ;
- mêmes codes autorisés entre tenants différents ;
- scopes ;
- lifecycle.

### Commit proposé

```text
test(store): cover multi-store administration workflow
```

---

## Étape 1.9.3 — Parcours invitation

Test :

```text
Owner
→ invite User B
→ User B accepts
→ membership ACTIVE
→ role assignments applied
```

Cas négatifs :

```text
expired token
cancelled invitation
already used token
wrong authenticated email
cross-tenant invitation access
```

### Commit proposé

```text
test(identity): cover invitation lifecycle
```

---

## Étape 1.9.4 — Parcours permissions et scopes

Scénario :

```text
User B
role = STORE_MANAGER
scope = Store A only

update Store A → SUCCESS
update Store B → DENIED
```

### Commit proposé

```text
test(access): verify store scoped authorization
```

---

## Étape 1.9.5 — Last owner invariant

Tester :

```text
one ACTIVE owner
→ suspend owner
→ DENIED
```

```text
one ACTIVE owner
→ revoke owner
→ DENIED
```

```text
two ACTIVE owners
→ revoke one owner
→ SUCCESS
```

### Commit proposé

```text
test(access): enforce last active owner invariant
```

---

## Étape 1.9.6 — Tenant isolation

Créer :

```text
Organization A
Organization B
```

Tester au minimum :

```text
Actor A
→ Organization B
→ NOT_FOUND / DENIED according to endpoint contract

Actor A
→ Store B
→ NOT_FOUND

Actor A
→ Membership B
→ NOT_FOUND

Actor A
→ Invitation B
→ NOT_FOUND
```

Vérifier également que les repositories imposent le tenant dans leurs lookups.

### Commit proposé

```text
test(tenant): verify strict tenant isolation
```

---

## Étape 1.9.7 — Révocation immédiate

Test :

```text
User B authenticated
authorizationVersion = N

Owner revokes / suspends membership
authorizationVersion = N+1

User B performs sensitive action
→ denied
```

### Commit proposé

```text
test(auth): verify immediate membership revocation
```

---

## Étape 1.9.8 — Atomicité audit et outbox

Injecter des échecs :

```text
before audit persistence
before outbox persistence
before commit
```

Résultat attendu :

```text
zero partial administrative effect
```

### Commit proposé

```text
test(audit): verify administration transaction atomicity
```

---

## Definition of Done — Epic 1.9

- parcours heureux complet ;
- tests négatifs significatifs ;
- isolation tenant prouvée ;
- scopes prouvés ;
- last owner invariant prouvé ;
- révocation immédiate prouvée ;
- audit/outbox atomiques ;
- tests PostgreSQL réels.

---
