# Zandu Sales Manager — Lot 1 : Administration opérationnelle

**Version :** 1.0  
**Statut :** Terminé — Gate Lot 1 validé le 25 août 2026
**Langue :** Français — identifiants de code en anglais

---
# 1. Objectif du Lot 1

Le Lot 1 construit la première capacité métier réellement exploitable de Zandu Sales Manager : **l’administration opérationnelle d’un tenant**.

Il doit permettre à un utilisateur authentifié de :

- créer et administrer une `Organization` ;
- créer et administrer ses `Store` ;
- devenir et rester `ORGANIZATION_OWNER` selon les invariants définis ;
- inviter des collaborateurs ;
- accepter une invitation ;
- créer et maintenir les `OrganizationMembership` ;
- attribuer des rôles et permissions ;
- restreindre les droits à une organisation entière ou à une sélection de boutiques ;
- suspendre, réactiver ou révoquer les accès ;
- appliquer les guards opérationnels liés au statut d’une organization ou d’un store ;
- produire les audits de sécurité et domain events requis ;
- garantir l’isolation stricte entre tenants.

Le Lot 1 ne vise pas encore à implémenter :

```text
Catalog
Pricing
Sales
Inventory
Cash Management
Purchasing
Customers
Reporting
Offline
```

Ces contextes seront introduits dans les lots suivants.

Le Lot 1 est terminé uniquement lorsque son gate de sortie est satisfait.

---
# 2. Références d’architecture

Le Lot 1 doit respecter les décisions de la baseline DDD v1.1.

Principes applicables :

```text
User
→ identité globale

Organization
→ tenant fonctionnel

OrganizationMembership
→ accès d’un User à une Organization

Role
→ groupe de PermissionCode

RoleAssignment
→ rôle + AccessScope

AccessScope
→ ORGANIZATION | SELECTED_STORES
```

Contraintes essentielles :

```text
OrganizationId
= frontière stricte de tenant
```

```text
organizationId
actorId
← ActorContext
```

Ces valeurs ne doivent jamais être acceptées aveuglément depuis un payload métier externe.

Les handlers d’application suivent le flux :

```text
Application Handler
├── OrganizationOperationalGuard
├── AuthorizationService.authorize(...)
└── Domain Aggregate.execute(...)
```

Les repositories recherchent les données tenant-scoped par :

```text
organizationId + aggregateId
```

Une ressource appartenant à un autre tenant doit être traitée publiquement comme :

```text
NOT_FOUND
```

et non comme une révélation d’existence cross-tenant.

Conformément à l'ADR-0017, cette isolation applicative est complétée par
PostgreSQL Row Level Security sur toutes les tables tenant-owned. Le contexte
`app.organization_id` est résolu côté serveur et reste local à la transaction.
Les rôles applicatifs ne possèdent jamais `BYPASSRLS`.

---
# 3. Règle de commits

Le développement du Lot 1 suit une logique de **commits atomiques**.

Un commit doit :

- représenter une seule intention cohérente ;
- laisser le repository dans un état valide ;
- inclure les tests directement liés à la modification ;
- éviter de mélanger refactoring, feature et configuration sans nécessité ;
- conserver les architecture fitness tests au vert ;
- respecter les frontières des bounded contexts ;
- ne jamais introduire un raccourci temporaire qui contourne `Application\Contract`.

## 3.1 Convention de message

Format :

```text
<type>(<scope>): <description>
```

Types recommandés :

```text
feat
fix
refactor
test
build
ci
chore
docs
perf
```

Exemples :

```text
feat(organization): add organization aggregate
feat(identity): add organization membership lifecycle
feat(access): add role assignment scopes
feat(store): add store aggregate
test(tenant): enforce tenant-scoped repository access
feat(audit): record sensitive access operations
```

Les messages de commit restent en anglais.

---
# 4. Vue d’ensemble

```text
Epic 1.1 — Organization foundation
       ↓
Epic 1.2 — Store foundation
       ↓
Epic 1.3 — Organization invitations
       ↓
Epic 1.4 — Membership lifecycle
       ↓
Epic 1.5 — Roles, permissions & scopes
       ↓
Epic 1.5 bis — User accounts & onboarding
       ↓
Epic 1.5 ter — Authentication security hardening
       ↓
Epic 1.6 — Authorization & operational guards
       ↓
Epic 1.7 — Security audit & event integration
       ↓
Epic 1.8 — Administration API
       ↓
Epic 1.9 — Integration & tenant isolation tests
       ↓
Lot 1 Gate
```

---


## Dependencies

Dependencies are capability-based. Follow dependencies explicitly named by the current Epic or support file; do not infer dependencies from Lot numbering.


## Source of truth

`docs/specs/planning/zandu-lot-1-administration-operationnelle.md`

Open this complete specification only when the targeted AI files do not answer a required business question.
