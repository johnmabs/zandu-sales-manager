# 7. Epic 1.3 — Organization invitations

## Objectif

Permettre à un owner ou acteur autorisé d’inviter un collaborateur.

---

## Étape 1.3.1 — Ajouter `OrganizationInvitation`

Modèle proposé :

```text
OrganizationInvitation
├── OrganizationInvitationId
├── OrganizationId
├── email
├── invitedBy
├── tokenHash
├── expiresAt
├── status
├── intendedRoleAssignments
├── acceptedBy?
├── acceptedAt?
└── Version
```

Statuts :

```text
PENDING
ACCEPTED
EXPIRED
CANCELLED
```

Cette séparation formalise le workflow d’invitation sans surcharger `OrganizationMembership`.

### Commit proposé

```text
feat(identity): add organization invitation aggregate
```

---

## Étape 1.3.2 — Token d’invitation

Règles :

- token suffisamment aléatoire ;
- token brut affiché/transmis uniquement à la création ;
- seule une empreinte/hash est persistée ;
- usage unique ;
- expiration obligatoire ;
- token absent des logs, events et audits.

### Commit proposé

```text
feat(identity): add secure invitation tokens
```

---

## Étape 1.3.3 — Use case `InviteOrganizationMember`

Command métier :

```text
InviteOrganizationMember
├── email
├── intendedRoleAssignments
└── expiresAt?
```

Le tenant et l’acteur viennent de `ActorContext`.

Checks :

```text
Organization ACTIVE
actor authorized
role assignments valid
selected stores belong to organization
no conflicting active invitation according to policy
```

Event :

```text
OrganizationMemberInvited
```

### Commit proposé

```text
feat(identity): add member invitation use case
```

---

## Étape 1.3.4 — Accepter une invitation

Workflow :

```text
AcceptOrganizationInvitation
        ↓
validate token
        ↓
validate expiration
        ↓
resolve authenticated User
        ↓
verify invited email
        ↓
create or activate OrganizationMembership
        ↓
apply initial role assignments
        ↓
mark invitation ACCEPTED
```

Le workflow doit être atomique autant que possible dans la transaction locale.

### Event

```text
OrganizationInvitationAccepted
```

### Commit proposé

```text
feat(identity): add invitation acceptance workflow
```

---

## Étape 1.3.5 — Annulation et expiration

Ajouter :

```text
CancelOrganizationInvitation
ExpireOrganizationInvitations
```

L’expiration peut être calculée à la lecture et/ou matérialisée par job selon l’implémentation retenue.

### Commits proposés

```text
feat(identity): add invitation cancellation
feat(identity): handle invitation expiration
```

---

## Definition of Done — Epic 1.3

- invitation à usage unique ;
- expiration testée ;
- annulation testée ;
- token brut non persisté ;
- acceptation idempotente ou conflit explicite ;
- tenant isolation prouvée.

---
