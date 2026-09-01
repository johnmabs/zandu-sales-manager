# 11. Epic 1.7 — Security audit & event integration

## Objectif

Rendre les opérations d’administration traçables et auditables.

---

## Étape 1.7.1 — `SecurityAuditEntry`

Modèle :

```text
SecurityAuditEntry
├── id
├── organizationId
├── ActorReference actor
├── SecurityAction action
├── ResourceReference target
├── AuditOutcome
├── reason?
├── SafeAuditMetadata
├── correlationId
├── causationId?
├── sessionId?
├── ipAddress?
├── userAgent?
└── occurredAt
```

Outcomes :

```text
SUCCESS
DENIED
FAILED
```

### Commit proposé

```text
feat(audit): add security audit entry
```

---

## Étape 1.7.2 — Actions auditées Lot 1

Au minimum :

```text
ORGANIZATION_CREATED
ORGANIZATION_UPDATED
ORGANIZATION_SUSPENDED
ORGANIZATION_REACTIVATED

STORE_CREATED
STORE_UPDATED
STORE_SUSPENDED
STORE_REACTIVATED

MEMBER_INVITED
MEMBER_SUSPENDED
MEMBER_REACTIVATED
MEMBER_REVOKED

ROLE_ASSIGNED
ROLE_REMOVED
OWNER_ASSIGNED
OWNER_REMOVED
```

### Commit proposé

```text
feat(audit): add administration security actions
```

---

## Étape 1.7.3 — Audit des refus sensibles

Les refus d’opérations sensibles doivent produire un audit séparé lorsque requis.

Ne jamais journaliser :

```text
password
access token
refresh token
invitation token
payment secret
raw request payload
```

### Commit proposé

```text
feat(audit): record sensitive authorization denials
```

---

## Étape 1.7.4 — Transaction métier + audit + outbox

Pour une action sensible :

```text
aggregate change
+ required security audit
+ outbox message
= same local transaction
```

Exemple :

```text
SuspendOrganizationMembership
→ membership.suspend()
→ SecurityAuditEntry
→ OrganizationMembershipSuspended
→ OutboxMessage
→ COMMIT
```

### Commit proposé

```text
feat(audit): coordinate audit and outbox transactionally
```

---

## Definition of Done — Epic 1.7

- événements Lot 1 enveloppés correctement ;
- audits append-only ;
- données sensibles absentes des audits ;
- correlation/causation propagées ;
- atomicité audit + métier + outbox testée.

---
