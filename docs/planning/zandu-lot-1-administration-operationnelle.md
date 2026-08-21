# Zandu Sales Manager — Lot 1 : Administration opérationnelle

**Version :** 1.0  
**Statut :** Backlog d’implémentation  
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

# 5. Epic 1.1 — Organization foundation

## Objectif

Créer le bounded context `Organization` et son aggregate racine `Organization`.

---

## Étape 1.1.1 — Créer le module Organization

Créer la structure :

```text
src/Modules/Organization/
├── Domain/
├── Application/
│   └── Contract/
├── Infrastructure/
└── Presentation/
    └── Api/
```

Le module doit respecter les mêmes règles architecturales que les modules du Lot 0.

### Validation

- architecture fitness tests verts ;
- aucune dépendance vers un autre `Domain` ;
- aucun usage Doctrine/Symfony dans `Domain`.

### Commit proposé

```text
refactor(organization): add bounded context structure
```

---

## Étape 1.1.2 — Ajouter l’aggregate `Organization`

Modèle initial :

```text
Organization
├── OrganizationId id
├── name
├── status
├── countryCode
├── defaultCurrency
├── defaultTimeZone
├── defaultLocale
├── lifecycle actors and timestamps
└── Version version
```

Statuts :

```text
ACTIVE
SUSPENDED
CLOSURE_PENDING
CLOSED
```

Transitions normales :

```text
ACTIVE
→ SUSPENDED
→ ACTIVE
```

ou :

```text
ACTIVE / SUSPENDED
→ CLOSURE_PENDING
→ CLOSED
```

Règles :

- `CLOSED` est terminal dans le workflow normal ;
- `DeleteOrganization` n’existe pas comme opération métier ;
- une organization suspendue refuse les nouvelles opérations métier ;
- les opérations de remédiation ou de terminaison explicitement autorisées peuvent rester possibles.

### Domain events

```text
OrganizationCreated
OrganizationUpdated
OrganizationSuspended
OrganizationReactivated
OrganizationClosureRequested
OrganizationClosed
```

### Commit proposé

```text
feat(organization): add organization aggregate lifecycle
```

---

## Étape 1.1.3 — Ajouter les value objects Organization

Créer uniquement les concepts réellement nécessaires :

```text
OrganizationName
CountryCode
Locale
TimeZone
```

Réutiliser les primitives du `SharedKernel` lorsque la responsabilité y appartient déjà :

```text
OrganizationId
Currency
Clock
ActorContext
```

### Commit proposé

```text
feat(organization): add organization value objects
```

---

## Étape 1.1.4 — Ajouter `OrganizationRepository`

Contrat côté module :

```text
OrganizationRepository
```

Opérations minimales :

```text
save(...)
get(...)
find(...)
```

Toute lecture par identifiant doit rester tenant-safe selon le contexte d’utilisation.

Pas de `GenericRepository`.

### Commit proposé

```text
feat(organization): add organization repository contract
```

---

## Étape 1.1.5 — Persistence Doctrine de Organization

Ajouter :

- mapping Doctrine ;
- repository Doctrine ;
- migration PostgreSQL ;
- contraintes utiles ;
- version optimiste si nécessaire.

### Validation

- round-trip PostgreSQL réel ;
- création ;
- modification ;
- suspension ;
- réactivation ;
- aucun float ;
- timestamps cohérents.

### Commit proposé

```text
feat(organization): persist organization aggregate
```

---

## Étape 1.1.6 — Use case `CreateOrganization`

Command :

```text
CreateOrganization
├── name
├── countryCode
├── defaultCurrency
├── defaultTimeZone
└── defaultLocale
```

`organizationId` et `actorId` sont créés/résolus côté serveur.

Résultat :

```text
Organization
status = ACTIVE
```

Le use case doit produire :

```text
OrganizationCreated
```

La création de l’owner initial sera coordonnée avec `Identity & Access` dans un use case applicatif dédié du Lot 1.

### Commit proposé

```text
feat(organization): add create organization use case
```

---

## Étape 1.1.7 — Lifecycle Organization

Ajouter séparément :

```text
UpdateOrganization
SuspendOrganization
ReactivateOrganization
RequestOrganizationClosure
```

Éviter un seul PATCH générique capable de modifier le `status`.

### Commits proposés

```text
feat(organization): add organization profile update
feat(organization): add organization suspension lifecycle
feat(organization): add organization closure request
```

---

## Definition of Done — Epic 1.1

- aggregate `Organization` fonctionnel ;
- lifecycle testé ;
- persistence PostgreSQL réelle ;
- aucun delete métier ;
- domain events enregistrés ;
- invariants testés ;
- architecture tests verts.

---

# 6. Epic 1.2 — Store foundation

## Objectif

Permettre à une `Organization` d’exploiter plusieurs boutiques.

---

## Étape 1.2.1 — Ajouter l’aggregate `Store`

Modèle :

```text
Store
├── StoreId id
├── OrganizationId organizationId
├── StoreCode code
├── name
├── status
├── address?
├── timeZone
├── currency
├── locale
├── lifecycle actors and timestamps
└── Version version
```

Contrainte :

```text
UNIQUE (organization_id, store_code)
```

Invariants :

- `organizationId` est immuable ;
- `StoreCode` devient immuable après création ;
- pour le MVP :

```text
Store.currency == Organization.defaultCurrency
```

- les timestamps sont enregistrés en UTC ;
- la journée métier utilise `Store.timeZone` ;
- un store `CLOSED` ne redevient pas `ACTIVE` dans le workflow normal.

### Commit proposé

```text
feat(store): add store aggregate
```

---

## Étape 1.2.2 — Ajouter les value objects Store

Créer selon besoin :

```text
StoreCode
StoreName
StoreAddress
```

Ne pas dupliquer `Currency`, `TimeZone`, `Locale` si les primitives existantes conviennent.

### Commit proposé

```text
feat(store): add store value objects
```

---

## Étape 1.2.3 — Ajouter `StoreRepository`

Le repository doit toujours respecter :

```text
OrganizationId + StoreId
```

Une boutique d’une autre organization ne doit jamais être retournée par un lookup tenant-scoped.

### Commit proposé

```text
feat(store): add store repository contract
```

---

## Étape 1.2.4 — Persistence Doctrine de Store

Ajouter :

- mapping ;
- repository ;
- migration ;
- contrainte unique `(organization_id, store_code)` ;
- activation et policy RLS tenant-safe conformément à l'ADR-0017 ;
- tests PostgreSQL.

### Commit proposé

```text
feat(store): persist store aggregate
```

---

## Étape 1.2.5 — Use cases Store

Créer :

```text
CreateStore
UpdateStore
SuspendStore
ReactivateStore
RequestStoreClosure
CancelStoreClosure
```

Domain events :

```text
StoreCreated
StoreUpdated
StoreSuspended
StoreReactivated
StoreClosureRequested
StoreClosureCancelled
```

### Commits proposés

```text
feat(store): add create store use case
feat(store): add store update use case
feat(store): add store suspension lifecycle
feat(store): add store closure request
```

---

## Étape 1.2.6 — Introduire `StoreClosure`

Créer le process manager :

```text
StoreClosure
├── StoreClosureId
├── OrganizationId
├── StoreId
├── status
├── reason
├── blockers
├── requestedBy
├── requestedAt
├── completedBy?
├── completedAt?
└── Version
```

Statuts :

```text
REQUESTED
IN_PROGRESS
READY
COMPLETED
CANCELLED
```

La fermeture définitive devra pouvoir vérifier plus tard :

```text
CashSession OPEN
StockTransfer in transit
StockCount OPEN / FINALIZING
Purchasing documents open
Stock quantity remaining
```

Ne pas importer les repositories de ces futurs modules.

Préparer un contrat public du type :

```text
StoreClosureBlockerProvider
```

ou une abstraction équivalente dans la couche appropriée.

Le Lot 1 doit pouvoir fonctionner lorsque les modules futurs ne sont pas encore présents.

### Commit proposé

```text
feat(store): add store closure process manager
```

---

## Definition of Done — Epic 1.2

- plusieurs stores par organization ;
- unicité du `StoreCode` dans le tenant ;
- lifecycle testé ;
- recherche cross-tenant impossible ;
- persistence réelle ;
- process manager de fermeture prêt à accueillir les blockers futurs.

---

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

# 9 bis. Epic 1.5 bis — User accounts & onboarding

## Objectif

Combler le prérequis d'identité absent du cadrage initial : permettre à une
personne sans compte de devenir le premier owner d'une organisation ou de
rejoindre une organisation depuis une invitation.

## Parcours livrés

```text
POST /api/auth/register
POST /api/auth/invitations/{token}/register
POST /api/auth/login
```

- compte `User` global identifié par un email canonique unique ;
- mot de passe haché avec Argon2id et jamais persisté en clair ;
- inscription atomique du premier utilisateur, de son organisation, de son
  membership actif et de son rôle `ORGANIZATION_OWNER` ;
- inscription atomique d'un invité sans compte, avec validation de l'email et
  consommation à usage unique du token ;
- authentification des comptes persistés avec résolution du membership actif ;
- organisation par défaut conservée pour établir le contexte tenant initial ;
- identité `User` globale, hors RLS tenant conformément à l'ADR-0018 ; son accès
  SQL runtime actuel doit être réduit avant la production.

Le choix ou changement d'organisation pour un utilisateur multi-organisation,
la récupération de mot de passe et la vérification d'adresse email restent des
sujets d'authentification ultérieurs. Ils ne sont pas déclarés livrés par cet
Epic correctif.

## Definition of Done — Epic 1.5 bis

- un visiteur peut créer son compte et sa première organisation ;
- une personne invitée sans compte peut s'inscrire avec son token ;
- les deux profils peuvent ensuite obtenir un JWT via `/api/auth/login` ;
- la création du compte et de ses accès initiaux est transactionnelle ;
- les parcours sont couverts par des tests API et PostgreSQL réels.

---

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

# 12. Epic 1.8 — Administration API

## Objectif

Exposer les capacités du Lot 1 via l’API sans transformer les aggregates en CRUD générique.

---

## Étape 1.8.1 — Organization API

Endpoints candidats :

```text
POST /organizations
GET /organizations/{id}
PATCH /organizations/{id}

POST /organizations/{id}/suspend
POST /organizations/{id}/reactivate
POST /organizations/{id}/closure-request
```

Les transitions métier restent des opérations explicites.

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

# 14. Scénario de démonstration du Lot 1

Le Lot 1 doit pouvoir démontrer le workflow suivant de bout en bout :

```text
1. User A s’authentifie.

2. User A crée Organization Alpha.

3. User A devient ORGANIZATION_OWNER
   de Organization Alpha.

4. User A crée Store Brazzaville Centre.

5. User A crée Store Poto-Poto.

6. User A invite User B.

7. User B accepte l’invitation.

8. User A attribue à User B :
   role = STORE_MANAGER
   scope = SELECTED_STORES
   stores = [Brazzaville Centre]

9. User B modifie Brazzaville Centre.
   → SUCCESS

10. User B tente de modifier Poto-Poto.
    → DENIED

11. User A suspend User B.
    → accès immédiatement refusé.

12. User A réactive User B.
    → accès rétabli.

13. User A tente de retirer son propre rôle
    alors qu’il est le seul owner actif.
    → DENIED

14. Un second owner est ajouté.

15. Le premier owner peut alors être retiré.

16. Tous les domain events, security audits,
    correlation IDs et outbox messages requis existent.

17. Aucun accès cross-tenant n’est possible.
```

---

# 15. CI minimale du Lot 1

Le pipeline existant du Lot 0 doit rester actif.

Ajouter progressivement :

```text
architecture fitness tests
↓
domain unit tests
↓
authorization tests
↓
PostgreSQL integration tests
↓
tenant isolation tests
↓
invitation security tests
↓
transaction / outbox tests
↓
API contract tests
```

Commits atomiques possibles :

```text
ci(test): add organization domain test job
ci(test): add tenant isolation integration tests
ci(test): add administration API contract tests
```

Ne créer ces commits séparément que si le pipeline nécessite réellement une évolution structurelle.

---

# 16. Gate de sortie du Lot 1

Le Lot 1 est `DONE` uniquement lorsque :

```text
[ ] Organization aggregate opérationnel
[ ] Organization lifecycle testé
[ ] Store aggregate opérationnel
[ ] Store lifecycle testé
[ ] multi-store support validé
[ ] OrganizationInvitation opérationnelle
[ ] invitation token sécurisé
[ ] invitation expiry/cancel/accept testés
[ ] OrganizationMembership opérationnel
[ ] membership lifecycle testé
[ ] authorizationVersion effectif
[ ] permission catalog Lot 1 disponible
[ ] rôles système MVP disponibles
[ ] RoleAssignment disponible
[ ] AccessScope ORGANIZATION disponible
[ ] AccessScope SELECTED_STORES disponible
[ ] invariant du dernier owner protégé
[ ] compte utilisateur persistant disponible
[ ] inscription du premier owner disponible
[ ] inscription depuis une invitation disponible
[ ] AuthorizationService utilisé par les handlers
[ ] OrganizationOperationalGuard actif
[ ] StoreOperationalGuard actif
[ ] tenant-scoped repositories
[ ] PostgreSQL RLS actif sur toutes les tables tenant-owned
[ ] rôle applicatif sans BYPASSRLS
[ ] contexte tenant transactionnel sans fuite entre connexions
[ ] aucune fuite d’existence cross-tenant
[ ] SecurityAuditEntry opérationnel
[ ] opérations sensibles auditées
[ ] audit + métier + outbox atomiques
[ ] API administration disponible
[ ] OpenAPI à jour
[ ] tests PostgreSQL réels au vert
[ ] architecture fitness tests au vert
[ ] CI au vert
[ ] démonstration métier du Lot 1 réussie
[ ] documentation d’architecture mise à jour si une décision DÉCIDÉ a changé
[ ] ADR créé ou mis à jour pour toute nouvelle décision structurante
```

---

# 17. Hors périmètre du Lot 1

Ne pas introduire prématurément :

```text
Product
Pricing
Tax
Stock
StockMovement
CashRegister
CashSession
CashMovement
Sale
Payment
Customer
Supplier
PurchaseReceipt
Reporting projection
Offline sync
```

Des contrats minimaux peuvent être préparés uniquement lorsqu’ils sont indispensables à un workflow du Lot 1, notamment pour `StoreClosure`.

---

# 18. Préparation du Lot 2

Après validation du Lot 1, la prochaine étape recommandée est :

```text
Lot 2 — Catalog & basic Pricing
```

Objectif futur :

```text
Organization
      ↓
Catalog
      ↓
Product vendable
      ↓
basic Pricing
```

sans encore finaliser la vente.

La séquence cible reste :

```text
Lot 1
Identity / Organization / Store

        ↓

Lot 2
Catalog / basic Pricing

        ↓

Lot 3
Inventory / Cash foundations

        ↓

Lot 4
Sales / CompleteSale cash

        ↓

M2
Première vente cash
```

---

# 19. Principe de travail pour la suite

Pour chaque étape :

1. vérifier la baseline DDD concernée ;
2. identifier le bounded context propriétaire ;
3. définir l’invariant avant l’implémentation ;
4. implémenter la plus petite tranche cohérente ;
5. ajouter les tests dans le même commit lorsque directement liés ;
6. exécuter architecture tests et tests métier ;
7. proposer un commit atomique ;
8. ne passer à l’étape suivante qu’après validation ;
9. mettre à jour la documentation seulement si l’état réel ou une décision structurante évolue.

Le repository réel reste la source de vérité sur l’avancement d’implémentation.
