# 12. Epic 1.8 — Administration API

## Objectif

Exposer les capacités du Lot 1 via l’API sans transformer les aggregates en CRUD générique.

---

## Étape 1.8.1 — Organization API

Endpoints candidats :

```text
GET /organizations/{id}
PATCH /organizations/{id}

POST /organizations/{id}/suspend
POST /organizations/{id}/reactivate
POST /organizations/{id}/closure-request
```

Les transitions métier restent des opérations explicites.

La création est temporairement limitée à la première organisation via
`POST /auth/register` : un owner ne peut posséder qu'une seule organisation
tant que la sélection d'une organisation active et le renouvellement des tokens
ne sont pas implémentés. Le cas d'usage `CreateOrganization` reste interne pour
préserver l'évolution future vers le multi-organisation.

### Commit proposé

```text
feat(api): expose organization administration endpoints
```

---

## Étape 1.8.2 — Store API

Endpoints :

```text
GET /stores
POST /stores
GET /stores/{id}
PATCH /stores/{id}

POST /stores/{id}/suspend
POST /stores/{id}/reactivate
POST /stores/{id}/closure-request
POST /stores/{id}/closure-request/cancel
```

Toutes les collections sont tenant-scoped.

### Commit proposé

```text
feat(api): expose store administration endpoints
```

---

## Étape 1.8.3 — Invitation API

Endpoints :

```text
POST /member-invitations
POST /member-invitations/{id}/cancel
POST /invitations/{token}/accept
```

Ne jamais exposer le `tokenHash`.

### Commit proposé

```text
feat(api): expose organization invitation endpoints
```

---

## Étape 1.8.4 — Membership API

Endpoints :

```text
GET /members
GET /members/{id}

POST /members/{id}/suspend
POST /members/{id}/reactivate
POST /members/{id}/revoke
```

### Commit proposé

```text
feat(api): expose membership administration endpoints
```

---

## Étape 1.8.5 — Role assignment API

Endpoints :

```text
GET /roles

POST /members/{id}/role-assignments
DELETE /members/{id}/role-assignments/{assignmentId}
```

La suppression d’une attribution reste une commande métier soumise à l’invariant du dernier owner.

### Commit proposé

```text
feat(api): expose role assignment endpoints
```

---

## Étape 1.8.6 — OpenAPI et error contract

Documenter au minimum :

```text
400 VALIDATION_ERROR
401 UNAUTHENTICATED
403 FORBIDDEN
404 NOT_FOUND
409 CONFLICT
422 DOMAIN_RULE_VIOLATION
```

Une ressource cross-tenant doit suivre la stratégie `NOT_FOUND`.

### Commit proposé

```text
docs(api): document administration API contracts
```

---

## Definition of Done — Epic 1.8

- OpenAPI valide ;
- aucun aggregate Doctrine exposé directement ;
- commands intentionnelles pour les transitions ;
- erreurs stables ;
- authorization côté serveur ;
- tenant isolation respectée.

---
