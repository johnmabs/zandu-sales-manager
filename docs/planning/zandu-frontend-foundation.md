# Zandu Sales Manager — Frontend Foundation

**Version :** 1.0  
**Statut :** Backlog d’implémentation / baseline frontend préparatoire  
**Langue :** Français — identifiants de code en anglais

---

# 1. Objectif

Le `Frontend Foundation` joue pour les interfaces Zandu le même rôle que le Lot 0 côté backend :

> construire un socle exécutable, cohérent, testable et suffisamment stable avant l’implémentation massive des écrans métier.

Il doit préparer deux surfaces distinctes :

```text
Zandu Admin
+
Zandu POS
```

tout en partageant les primitives communes qui ne doivent pas être réimplémentées deux fois.

Le Foundation doit permettre de démarrer ensuite des tranches verticales frontend sans réinventer :

```text
authentication
API communication
organization context
store context
permissions
error handling
design tokens
forms
tables
money / quantity display
testing
observability
CI
```

---

# 2. Décisions déjà figées par la baseline

## DÉCIDÉ — Back-office

```text
TypeScript
React
Next.js
```

Le back-office est une application Web.

---

## DÉCIDÉ — POS

```text
TypeScript
React
Vite
Tauri
```

Cible MVP :

```text
Windows 10/11 x64
```

---

## DÉCIDÉ — Autorité métier

```text
Symfony backend
=
source of truth métier
```

Ni :

```text
Next.js
```

ni :

```text
Tauri
```

ne deviennent un second backend métier.

Les interfaces peuvent :

- orchestrer l’UX ;
- maintenir du state local ;
- préparer des commands ;
- afficher des projections ;
- valider des contraintes UX ;

mais les invariants métier restent côté Symfony.

---

## DÉCIDÉ — API

```text
REST
+
API Platform
+
OpenAPI
```

Le frontend doit s’appuyer sur ce contrat plutôt que dupliquer manuellement les DTO lorsque cela peut être évité.

---

## DÉCIDÉ — Authentication MVP

```text
JWT access token
+
refresh token rotation
```

Le frontend doit gérer le lifecycle de session sans contourner les règles serveur.

---

## DÉCIDÉ — POS local storage futur

```text
SQLite
```

SQLite est destiné au stockage opérationnel local du POS.

Il n’est pas une réplique PostgreSQL.

Cependant :

```text
Offline
Synchronization
OfflineCommand
LocalOperationLedger
SyncState
```

restent hors du premier Frontend Foundation.

Le Foundation doit seulement éviter une architecture qui empêcherait leur introduction future.

---

# 3. Décisions Frontend Foundation à distinguer

Convention :

```text
DÉCIDÉ
→ issu de la baseline actuelle

PROPOSÉ
→ choix recommandé pour ce Foundation

OUVERT
→ nécessite validation / ADR avant figement
```

Aucun choix `PROPOSÉ` ne doit être transformé silencieusement en décision d’architecture.

---

# 4. Architecture générale proposée

## PROPOSÉ — Workspace frontend commun

```text
frontend/
├── apps/
│   ├── admin/
│   └── pos/
│
├── packages/
│   ├── api-client/
│   ├── auth/
│   ├── authorization/
│   ├── ui/
│   ├── design-tokens/
│   ├── domain-formatting/
│   ├── shared/
│   ├── testing/
│   └── config/
│
├── package.json
├── tsconfig.base.json
└── ...
```

Applications :

```text
apps/admin
→ Next.js + React + TypeScript
```

```text
apps/pos
→ React + Vite + TypeScript
→ Tauri shell
```

---

# 5. Pourquoi deux applications

Admin et POS ont des contraintes UX différentes.

## Admin

Optimisé pour :

```text
navigation
tables
filters
forms
configuration
operations management
audit
desktop web
```

## POS

Optimisé pour :

```text
speed
keyboard usage
barcode scanner
touch
minimal navigation
cart
cash session
payment
receipt
future offline operation
```

Fusionner les deux interfaces dans une seule application créerait :

- couplage UX ;
- bundles inutiles ;
- navigation artificielle ;
- dépendances POS dans l’Admin ;
- dépendances Admin dans le POS ;
- difficulté future pour Tauri/offline.

---

# 6. Ce qui doit être partagé

Partager uniquement les éléments réellement transversaux :

```text
API contracts
authentication primitives
authorization helpers
organization/store context types
Money formatting
Quantity formatting
Date/time formatting
error contract
design tokens
basic UI primitives
testing helpers
```

Ne pas créer un énorme package :

```text
shared-everything
```

qui deviendrait un monolithe frontend.

---

# 7. Dépendances autorisées

Direction recommandée :

```text
apps/admin
 ├── api-client
 ├── auth
 ├── authorization
 ├── ui
 ├── domain-formatting
 └── shared
```

```text
apps/pos
 ├── api-client
 ├── auth
 ├── authorization
 ├── ui
 ├── domain-formatting
 └── shared
```

Packages bas niveau :

```text
shared
design-tokens
```

ne dépendent pas des applications.

Interdit :

```text
packages/ui → apps/admin
packages/auth → apps/pos
api-client → UI
```

---

# 8. Epic F0.1 — Workspace

## Objectif

Créer un workspace capable de lancer séparément :

```text
Admin
POS Web dev
POS Tauri
```

## PROPOSÉ

Utiliser un gestionnaire de workspace Node moderne avec lockfile unique.

Le choix exact :

```text
pnpm workspace
```

est recommandé, mais doit être figé par ADR si nécessaire.

Arborescence initiale :

```text
frontend/
├── apps/
│   ├── admin/
│   └── pos/
├── packages/
│   ├── shared/
│   └── config/
└── package.json
```

Validation :

```text
admin starts
pos starts
typecheck works
workspace dependency resolution works
```

Commit proposé :

```text
build(frontend): initialize frontend workspace
```

---

# 9. Epic F0.2 — Admin bootstrap

Créer :

```text
apps/admin
```

avec :

```text
Next.js
React
TypeScript
```

Activer :

```text
strict TypeScript
```

Objectif initial :

```text
GET /
→ renders Zandu Admin shell placeholder
```

Ne pas introduire immédiatement les modules métier.

Commit :

```text
build(admin): initialize Next.js application
```

---

# 10. Epic F0.3 — POS bootstrap

Créer :

```text
apps/pos
```

avec :

```text
React
TypeScript
Vite
```

Puis intégrer le runtime :

```text
Tauri
```

Validation :

```text
npm/pnpm dev
→ browser dev mode
```

et :

```text
tauri dev
→ desktop window
```

La cible MVP reste :

```text
Windows 10/11 x64
```

Commit :

```text
build(pos): initialize React Vite Tauri application
```

---

# 11. Epic F0.4 — TypeScript conventions

Configurer un socle partagé :

```text
tsconfig.base.json
```

Principes :

```text
strict = true
no implicit any
no unchecked blind casting
no duplicated API entity models when generated contract exists
```

Éviter :

```ts
const response: any = ...
```

et :

```ts
as SomeType
```

comme stratégie normale de validation réseau.

Commit :

```text
build(frontend): configure strict TypeScript
```

---

# 12. Epic F0.5 — Lint / Format / Imports

Le Foundation doit fixer :

```text
linting
formatting
import ordering
unused code policy
module boundaries
```

Les règles doivent être identiques dans les deux apps lorsque pertinent.

Commandes racine :

```text
lint
format
format:check
typecheck
test
build
```

Commit :

```text
chore(frontend): add code quality tooling
```

---

# 13. Epic F0.6 — Design tokens

Créer :

```text
packages/design-tokens
```

Contenir uniquement les primitives visuelles stables :

```text
spacing
radius
typography
breakpoints
z-index
semantic colors/tokens
```

Ne pas coupler les tokens à une feature métier.

Objectif :

```text
Admin
+
POS
```

partagent l’identité Zandu, même si leurs composants et densités diffèrent.

Commit :

```text
feat(ui): add shared design tokens
```

---

# 14. Epic F0.7 — UI primitives

Créer :

```text
packages/ui
```

Composants de base :

```text
Button
IconButton
Input
Textarea
Select
Checkbox
Radio
Dialog
Drawer
DropdownMenu
Tooltip
Badge
Alert
Toast
Spinner
Skeleton
EmptyState
ErrorState
Pagination primitives
```

Ces composants ne connaissent pas :

```text
Product
Sale
Stock
Supplier
```

Ils restent génériques.

---

# 15. UI Admin vs POS

Ne pas forcer tous les composants à être identiques.

Exemple :

```text
Shared Button primitive
```

peut être commun.

Mais :

```text
AdminDataTable
```

et :

```text
PosProductTile
```

restent spécifiques à leurs applications ou features.

---

# 16. Epic F0.8 — Formatting métier

Créer :

```text
packages/domain-formatting
```

Responsabilités :

```text
formatMoney
formatQuantity
formatBusinessDate
formatDateTime
formatPercentage
```

Important :

```text
Money
Quantity
```

ne doivent jamais passer par des calculs JS flottants non maîtrisés pour les règles métier.

Le frontend affiche et saisit.

Le backend reste autorité des calculs métier définitifs.

---

# 17. Decimal transport

Le client doit traiter les décimaux API sous forme sûre.

Recommandation :

```text
API decimal
→ string
```

et non :

```text
API decimal
→ JavaScript number
```

pour les valeurs nécessitant exactitude.

Exemple :

```ts
type DecimalString = string;
```

Types plus forts possibles :

```ts
type MoneyAmount = DecimalString;
type QuantityAmount = DecimalString;
```

sans prétendre reproduire entièrement `brick/math` côté frontend.

---

# 18. Epic F0.9 — API client

Créer :

```text
packages/api-client
```

Architecture :

```text
Feature UI
   ↓
feature service / hooks
   ↓
typed API client
   ↓
HTTP
   ↓
Symfony API
```

Interdit comme pratique normale :

```ts
fetch('/api/...') 
```

directement dans chaque composant.

---

# 19. OpenAPI

## DÉCIDÉ

Le backend expose OpenAPI.

## PROPOSÉ

Générer les types et/ou client TypeScript depuis le contrat OpenAPI.

Objectifs :

- réduire duplication DTO ;
- détecter changements de contrat ;
- accélérer intégration ;
- éviter divergence frontend/backend.

Le code généré doit être isolé :

```text
packages/api-client/src/generated/
```

et ne pas être modifié manuellement.

---

# 20. API boundary

Structure possible :

```text
api-client/
├── generated/
├── transport/
├── errors/
└── resources/
```

Exemple :

```ts
salesApi.completeSale(...)
```

plutôt que :

```ts
generatedClient.someGeneratedOperationName(...)
```

partout dans l’UI.

Une petite façade stable peut protéger les features des changements du générateur.

---

# 21. Correlation ID

Lorsque pertinent, le client doit pouvoir :

- recevoir le correlation id serveur ;
- l’inclure dans les écrans d’erreur/support ;
- le transmettre dans les logs frontend sans exposer de secret.

Exemple UI :

```text
Une erreur est survenue.
Référence : 01J...
```

utile pour diagnostic.

---

# 22. Epic F0.10 — Error contract

Le frontend doit distinguer au minimum :

```text
validation error
authentication error
authorization denied
not found
business rule conflict
idempotency conflict
network error
server error
```

Ne pas afficher directement :

```text
HTTP 409
```

à l’utilisateur.

Transformer :

```text
backend error code
→ UX message
```

Exemple :

```text
INSUFFICIENT_STOCK
→ Stock insuffisant pour finaliser la vente.
```

---

# 23. ErrorMapper

Créer une couche :

```text
ErrorMapper
```

ou abstraction équivalente.

Entrée :

```text
ApiError
```

Sortie :

```text
UiError
├── title
├── message
├── fieldErrors?
├── correlationId?
├── retryable
└── action?
```

Les features peuvent enrichir le mapping pour leurs codes métier.

---

# 24. Epic F0.11 — Authentication

Le Foundation gère :

```text
login
access token lifecycle
refresh token rotation
logout
expired session
invalidated authorizationVersion
```

La stratégie exacte de stockage des tokens doit respecter l’ADR sécurité existant.

Ne pas inventer un stockage permanent de credentials côté client.

---

# 25. Auth state

Modèle frontend minimal :

```text
AuthState
├── status
├── actor?
├── expiresAt?
└── session metadata?
```

Status :

```text
UNKNOWN
AUTHENTICATING
AUTHENTICATED
UNAUTHENTICATED
REFRESHING
```

Éviter un simple :

```ts
const isLoggedIn = true;
```

qui ne représente pas correctement le bootstrap de session.

---

# 26. Session bootstrap

Au démarrage :

```text
App
 ↓
resolve session
 ↓
fetch current actor / effective access context
 ↓
build frontend access state
 ↓
render protected shell
```

Ne pas afficher brièvement des écrans protégés avant résolution de session.

---

# 27. Refresh concurrency

Plusieurs requêtes recevant simultanément une expiration ne doivent pas déclencher :

```text
10 refresh requests
```

Le client doit avoir une stratégie :

```text
single-flight refresh
```

puis reprendre les requêtes compatibles.

Si refresh échoue :

```text
clear session
→ login
```

---

# 28. Epic F0.12 — OrganizationContext

Après authentification, l’utilisateur peut appartenir à une ou plusieurs organizations selon évolution du produit.

Créer un contexte :

```text
OrganizationContext
├── activeOrganizationId
├── organizations
└── status
```

La sélection active doit être explicite.

Ne pas inférer une Organization depuis une route non validée.

---

# 29. Epic F0.13 — StoreContext

Certaines fonctionnalités nécessitent :

```text
activeStoreId
```

Mais toutes ne sont pas store-scoped.

Exemples organization-scoped :

```text
organization settings
memberships
some roles
global supplier views
```

Exemples store-scoped :

```text
cash session
stock
stock count
sale
purchase receipt
```

Le frontend doit donc distinguer :

```text
OrganizationContext
```

de :

```text
StoreContext
```

---

# 30. Store selector

Le sélecteur ne doit afficher que les stores accessibles.

Il doit gérer :

```text
0 stores
1 store
multiple stores
store suspended
store closed
scope changed while session active
```

Si accès révoqué :

```text
active store becomes invalid
→ context must be cleared/reselected
```

---

# 31. Epic F0.14 — Authorization frontend

La sécurité réelle reste serveur.

Le frontend utilise les permissions pour :

```text
show/hide actions
disable actions
protect navigation
explain unavailable actions
reduce invalid requests
```

Mais :

```text
frontend permission check
≠ security boundary
```

---

# 32. Permission model

Ne jamais faire :

```ts
if (user.role === 'MANAGER') {
    showButton();
}
```

Utiliser :

```ts
can('PRODUCT_CREATE')
```

ou :

```ts
can('STOCK_TRANSFER_SHIP', {
  storeId
})
```

Les rôles ne doivent pas devenir la logique applicative frontend.

---

# 33. EffectiveAccess

Modèle proposé :

```text
EffectiveAccess
├── organizationId
├── authorizationVersion
├── permissions[]
├── scope
└── accessibleStoreIds[]
```

La source doit être le serveur.

Le frontend ne reconstruit pas lui-même les règles de `RoleAssignment`.

---

# 34. Can component / hook

API possible :

```ts
const allowed = useCan('STORE_UPDATE', {
  storeId,
});
```

ou composant :

```tsx
<Can permission="STORE_UPDATE" storeId={storeId}>
  <EditStoreButton />
</Can>
```

Le hook doit considérer :

```text
permission
+
scope
```

---

# 35. Navigation authorization

La navigation Admin doit être calculée à partir des capacités.

Exemple :

```text
Catalog
```

visible si l’acteur possède une permission pertinente Catalog.

Mais une route appelée directement doit encore :

1. appliquer le frontend guard ;
2. appeler le backend ;
3. accepter que le backend puisse répondre DENIED.

---

# 36. Epic F0.15 — Server state

## PROPOSÉ

Séparer :

```text
server state
```

de :

```text
UI state
```

Le cache des données backend doit utiliser une solution spécialisée de query/cache.

Exemples de responsabilités :

```text
fetch
cache
invalidate
retry policy
loading
mutation
optimistic update when safe
```

Ne pas copier toutes les entités serveur dans un store global maison.

---

# 37. Query keys

Convention centralisée :

```text
organizations
stores
products
stock
suppliers
purchaseOrders
sales
```

Toujours inclure les scopes nécessaires.

Exemple :

```text
['stock', organizationId, storeId, filters]
```

Jamais :

```text
['stock']
```

si cela peut mélanger les tenants/stores dans le cache.

---

# 38. Tenant cache isolation

Invariant frontend :

> une entrée de cache dépendante d’une organization doit inclure `organizationId`.

Une entrée dépendante d’un store doit inclure :

```text
organizationId
+
storeId
```

À changement d’organization :

```text
invalidate / isolate relevant cache
```

pour éviter toute fuite visuelle cross-tenant.

---

# 39. UI state

Le state local/UI comprend par exemple :

```text
sidebar open
dialog open
table columns
local filters before submit
POS cart draft
temporary scanner state
```

Il ne faut pas utiliser le server-state cache comme remplaçant de tout state local.

---

# 40. POS cart

Même avant offline, le panier POS mérite une abstraction séparée :

```text
CartState
```

Il ne doit pas devenir un faux `Sale` domain aggregate frontend.

Le serveur reste autorité lors de :

```text
CompleteSale
```

Le panier représente :

```text
user intent / draft UX
```

---

# 41. Epic F0.16 — Forms

Standardiser :

```text
field state
validation
backend field errors
dirty state
submission
disabled state
unsaved changes
```

## Validation

Deux niveaux :

```text
client validation
→ UX rapide
```

```text
server validation
→ authoritative
```

Le frontend ne doit pas recopier des invariants complexes pour devenir source de vérité.

---

# 42. Schema validation

PROPOSÉ :

utiliser des schémas runtime côté frontend pour :

```text
forms
environment variables
selected local payloads
```

Le code généré OpenAPI couvre le contrat statique ; la validation runtime est ajoutée seulement là où elle apporte une vraie valeur.

---

# 43. Epic F0.17 — Tables Admin

Créer une abstraction adaptée aux besoins récurrents :

```text
pagination
sorting
filters
loading
empty
error
row actions
bulk actions later
```

Ne pas imposer une DataTable géante universelle à toutes les features.

Convention :

```text
URL
```

peut porter :

```text
page
sort
filters
search
```

pour permettre refresh/share/back.

---

# 44. Server-driven pagination

Pour les grandes ressources :

```text
Products
StockMovements
Sales
PurchaseOrders
Audit
```

préférer pagination serveur.

Ne pas charger 50 000 lignes pour filtrer dans le navigateur.

---

# 45. Epic F0.18 — Routing Admin

Structure conceptuelle :

```text
/login

/app
  /organization
  /stores
  /members
  /catalog
  /pricing
  /inventory
  /purchasing
  /cash
  /sales
```

La structure finale Next.js doit refléter les capacités UX, pas reproduire obligatoirement les namespaces backend.

---

# 46. Routing POS

Le POS doit avoir une navigation minimale.

Concept :

```text
/login
/setup
/register
/session
/sell
/receipt/:id
/returns
```

Le POS n’est pas une mini-version de l’Admin.

---

# 47. Epic F0.19 — Admin Shell

Le shell doit fournir :

```text
organization selector
store selector
main navigation
actor menu
session/logout
breadcrumbs where useful
global notifications
loading/error boundary
```

Desktop-first.

La densité d’information est assumée.

---

# 48. Epic F0.20 — POS Shell

Le shell doit fournir en permanence les informations opérationnelles essentielles :

```text
store
cash register
cashier
cash session status
connectivity status future
sync status future
```

Le POS évite :

```text
deep sidebar navigation
```

pendant la vente.

---

# 49. Keyboard-first POS

Le Foundation POS doit permettre des raccourcis sans dépendre d’eux.

Exemples futurs :

```text
focus product search
quantity change
checkout
cancel modal
```

Le scanner code-barres doit pouvoir fonctionner comme une entrée rapide sans bloquer l’usage manuel.

La gestion précise du scanner peut venir avec le vertical slice POS.

---

# 50. Accessibility

Le Foundation doit exiger :

```text
keyboard navigation
visible focus
labels
semantic controls
dialog focus management
error association
adequate contrast
```

Même le POS touch-friendly doit rester utilisable au clavier.

---

# 51. Responsive strategy

## Admin

```text
desktop-first
```

mais pages principales consultables sur tablette.

## POS

Cible principale :

```text
desktop / terminal
```

avec contrôles suffisamment grands pour usage tactile.

Pas d’objectif MVP d’expérience smartphone complète pour le POS Tauri Windows.

---

# 52. Epic F0.21 — Notifications

Différencier :

```text
toast
inline error
page error
blocking dialog
confirmation
```

Ne pas utiliser des toasts pour :

```text
critical business failure
```

Exemple :

```text
CompleteSale failed: INSUFFICIENT_STOCK
```

doit rester visible et actionnable.

---

# 53. Confirmations

Ne pas confirmer chaque clic.

Réserver confirmation renforcée aux opérations importantes :

```text
close cash session
post goods receipt
ship transfer
begin stock count finalization
archive entity
complete irreversible action
```

Le texte doit décrire l’impact métier.

---

# 54. Epic F0.22 — Idempotency-Key frontend

Les commandes critiques doivent pouvoir transmettre :

```text
Idempotency-Key
```

conformément au backend.

Le client doit conserver la même clé pour le retry de la même intention.

Il ne doit pas générer une nouvelle clé après simple timeout réseau si l’utilisateur tente de reprendre exactement la même commande.

Exemples :

```text
CompleteSale
PostGoodsReceipt
ShipStockTransfer
ReceiveStockTransfer
```

---

# 55. Mutation lifecycle

Une mutation critique doit distinguer :

```text
idle
submitting
unknown outcome
success
business failure
technical failure
```

Le cas :

```text
request timeout after server commit
```

est crucial.

Le frontend ne doit pas dire automatiquement :

```text
Échec
```

si le résultat réel est inconnu.

Il peut retry avec la même idempotency key.

---

# 56. Epic F0.23 — Network abstraction

Le transport HTTP commun doit centraliser :

```text
base URL
auth header/cookies according to ADR
correlation headers
idempotency key
content type
timeout policy
refresh handling
error decoding
```

Pas de configuration HTTP dispersée.

---

# 57. Environment configuration

Variables publiques minimales :

```text
API_BASE_URL
APP_ENV
observability configuration if required
```

Ne jamais exposer :

```text
JWT signing secret
database credentials
provider secrets
```

dans les bundles frontend.

Validation des variables au démarrage/build.

---

# 58. Epic F0.24 — Feature organization

## Admin

Proposition :

```text
apps/admin/src/
├── app/
├── features/
│   ├── organization/
│   ├── stores/
│   ├── access/
│   ├── catalog/
│   ├── pricing/
│   ├── inventory/
│   ├── purchasing/
│   ├── cash/
│   └── sales/
└── components/
```

Chaque feature regroupe :

```text
components
queries
mutations
schemas
mappers
route-specific logic
```

sans copier l’architecture DDD backend dossier par dossier.

---

# 59. POS feature organization

```text
apps/pos/src/
├── app/
├── features/
│   ├── auth/
│   ├── terminal/
│   ├── cash-session/
│   ├── product-search/
│   ├── cart/
│   ├── checkout/
│   ├── receipt/
│   └── returns/
├── platform/
│   └── tauri/
└── components/
```

Le dossier :

```text
platform/tauri
```

isole les capacités desktop.

---

# 60. Tauri boundary

Aucune feature React ne doit dépendre directement partout des APIs Tauri.

Prévoir une façade :

```text
DesktopPlatform
```

ou services ciblés :

```text
PrinterService
ScannerService
LocalStorageService
WindowService
```

selon les besoins futurs.

Cela garde le POS testable en navigateur.

---

# 61. Offline readiness sans offline

Le Foundation ne construit pas le Lot Offline.

Mais il évite :

```text
React component
→ directly knows SQLite schema
```

ou :

```text
component
→ directly calls Tauri SQL plugin
```

Les sources de données futures doivent pouvoir être substituées derrière des services/adapters.

---

# 62. Epic F0.25 — Testing pyramid

Minimum :

```text
unit tests
component tests
integration tests
end-to-end tests
```

## Unit

Pour :

```text
formatters
permission helpers
error mappers
pure state transitions
```

## Component

Pour :

```text
forms
dialogs
tables
POS controls
```

## Integration

Pour :

```text
feature + mocked API boundary
auth bootstrap
organization/store switching
```

## E2E

Pour les parcours critiques.

---

# 63. E2E Foundation scenarios

Avant les modules métier complexes :

```text
login success
login failure
session refresh
session expiration
logout

organization selection
store selection

permission allowed
permission denied
scope denied

API business error mapping
network error
idempotent retry behavior
```

---

# 64. Admin first vertical slice

Après Foundation, premier vertical slice recommandé :

```text
Stores
```

Parcours :

```text
Login
→ Organization
→ Store list
→ Store details
→ Create Store
→ Update Store
→ Suspend Store
→ permission denied case
→ scope case
```

Pourquoi Stores :

- domaine déjà fondamental ;
- CRUD contrôlé ;
- permissions/scopes ;
- organization context ;
- API errors ;
- forms ;
- lists ;
- mutations ;
- operational status.

C’est un excellent test du Foundation.

---

# 65. POS first vertical slice

Le premier slice POS ne doit pas commencer par une vente complète.

D’abord :

```text
Login
→ Store context
→ CashRegister selection
→ OpenCashSession
→ POS shell
```

Puis :

```text
Product search
→ Cart
→ CompleteSale cash
```

---

# 66. Epic F0.26 — Observability frontend

Objectifs :

```text
technical errors
route/load performance
API failures
correlationId
client version
environment
```

Ne jamais enregistrer :

```text
access tokens
refresh tokens
passwords
sensitive customer data
full payment secrets
```

La stratégie précise doit rester alignée avec l’observabilité globale.

---

# 67. Frontend version

Afficher une version technique accessible dans :

```text
Admin → About / diagnostics
POS → diagnostics
```

Exemple :

```text
clientVersion
buildCommit
environment
```

utile pour support et compatibilité API.

---

# 68. Epic F0.27 — CI

Pipeline frontend :

```text
install locked dependencies
lint
format check
typecheck
unit tests
component/integration tests
build admin
build pos web
Tauri build/check where practical
```

E2E peut être job séparé.

---

# 69. Dependency policy

Le Foundation doit éviter l’empilement de librairies pour la même responsabilité.

Exemple interdit :

```text
3 form libraries
2 query libraries
multiple competing UI kits
```

Chaque dépendance structurante doit justifier :

```text
problem solved
maintenance
bundle impact
desktop compatibility
Next.js compatibility
Tauri compatibility
```

---

# 70. Security baseline frontend

Le frontend doit respecter :

```text
no secrets in bundle
no trust in client permission
no sensitive token logging
no raw backend stack traces to user
no unsafe HTML injection
no tenant ID accepted blindly as authorization
```

Le backend reste la barrière de sécurité.

---

# 71. XSS / rendering

Par défaut :

```text
React escaping
```

Aucun HTML métier arbitraire ne doit être rendu via :

```ts
dangerouslySetInnerHTML
```

sans justification et sanitization explicite.

---

# 72. File handling future

Pour uploads futurs :

```text
CV
documents
receipts
attachments
```

le frontend ne doit pas traiter un fichier comme sûr parce que le navigateur l’accepte.

Le backend reste autorité sur :

```text
size
mime
scan
storage
authorization
```

---

# 73. Frontend architecture tests

Ajouter des règles automatisées simples pour empêcher :

```text
app imports from another app
ui imports feature
generated api imports app
shared imports Next.js
shared imports Tauri
```

Objectif :

```text
packages remain portable
```

---

# 74. Naming

Code :

```text
English
```

Exemples :

```text
OrganizationSwitcher
StoreSelector
PermissionGate
ApiError
MoneyText
QuantityInput
```

UI :

```text
French initially
```

selon locale pilote.

Ne pas mélanger des identifiants français/anglais dans le code.

---

# 75. Internationalization readiness

Même si le MVP peut démarrer en français :

- ne pas concaténer excessivement les textes ;
- centraliser les labels communs ;
- formatting date/number selon locale ;
- ne pas hardcoder `XAF` partout si la Currency vient du Store.

Une infrastructure i18n lourde n’est pas obligatoire dès F0 si elle n’est pas encore décidée.

---

# 76. Dates

Le backend stocke les timestamps UTC.

Le frontend affiche selon :

```text
Store.timeZone
```

pour les événements métier store-scoped.

Distinguer :

```text
technical timestamp
BusinessDate
```

Ne jamais calculer une `BusinessDate` définitive uniquement à partir du timezone navigateur.

---

# 77. Loading strategy

Différencier :

```text
initial page load
background refresh
mutation pending
route transition
```

Éviter de masquer toute la page avec un spinner global pour chaque requête.

Utiliser :

```text
skeleton
inline progress
button pending state
```

selon contexte.

---

# 78. Empty states

Chaque liste métier doit distinguer :

```text
no data exists
```

de :

```text
filters return no result
```

et :

```text
permission prevents data
```

Ne pas afficher le même écran vide pour ces cas.

---

# 79. Operational status UI

Organization / Store status :

```text
ACTIVE
SUSPENDED
CLOSED
```

doit être visible et influencer l’UX.

Exemple :

```text
Store SUSPENDED
→ actions nouvelles désactivées
→ actions de remédiation compatibles disponibles
```

Mais la décision finale reste backend.

---

# 80. Feature flags

Ne pas introduire un système de feature flags complexe par défaut.

Si nécessaire :

```text
build-time flags
server capabilities
```

peuvent apparaître plus tard.

Les permissions ne sont pas des feature flags.

---

# 81. Admin Dashboard

Le Foundation ne doit pas construire un dashboard analytique fictif.

Le vrai Reporting n’est pas encore livré.

Le premier dashboard peut être opérationnel/minimal :

```text
selected organization
selected store
quick navigation
open operational warnings
```

sans inventer de KPI non supportés par le backend.

---

# 82. POS persistence avant offline

Pour le premier POS online :

- ne pas utiliser SQLite comme cache métier complet prématurément ;
- isoler seulement les choix de persistance nécessaires ;
- préparer l’adapter futur.

Le vrai modèle :

```text
OfflineCommand
LocalOperationLedger
SyncState
```

arrive avec le lot Offline.

---

# 83. Questions ouvertes à trancher avant implémentation complète

## OUVERT — Workspace tooling

Choix recommandé :

```text
pnpm workspaces
```

Évaluer besoin réel d’un orchestrateur supplémentaire avant d’introduire Turborepo/Nx.

---

## OUVERT — UI implementation library

Choisir après un petit spike Admin + POS.

Critères :

```text
accessibility
customization
bundle
Next.js
Vite
Tauri
long-term maintenance
```

Ne pas verrouiller le design autour d’un kit trop tôt.

---

## OUVERT — Server-state library

Une solution dédiée est recommandée.

Décision à figer après spike simple.

---

## OUVERT — Form library / schema library

Même logique :

```text
choose one
standardize
```

---

## OUVERT — Token storage details

Doivent être alignés avec l’ADR JWT/security réel.

Le document Foundation ne modifie pas cette décision silencieusement.

---

## OUVERT — Admin rendering strategy

Next.js est DÉCIDÉ.

Mais le choix par écran entre :

```text
Server Component
Client Component
SSR
CSR
```

reste une décision d’implémentation.

Comme Symfony reste autorité et que l’Admin est fortement interactif/authentifié, il ne faut pas adopter SSR partout par réflexe.

---

# 84. Spikes Frontend recommandés

## Spike F-A — OpenAPI client

Prouver :

```text
Symfony OpenAPI
→ generated TypeScript
→ typed request
→ typed response
→ error decode
```

---

## Spike F-B — JWT refresh

Prouver :

```text
expired access token
→ single refresh
→ concurrent requests resume
→ refresh rotation
→ failed refresh logs out
```

---

## Spike F-C — Permission scopes

Prouver :

```text
Organization scope
Selected Stores scope
navigation filtering
button guard
backend DENIED remains authoritative
```

---

## Spike F-D — Shared UI

Prouver qu’un composant partagé peut être consommé par :

```text
Next.js Admin
+
Vite POS
```

sans dépendance runtime incompatible.

---

## Spike F-E — Tauri boundary

Prouver :

```text
POS React feature
→ platform adapter
→ Tauri implementation
```

et test navigateur sans Tauri.

---

# 85. Ordre d’implémentation recommandé

```text
F0.1 workspace
F0.2 Admin Next.js
F0.3 POS Vite/Tauri
F0.4 TypeScript strict
F0.5 lint/format
F0.6 design tokens
F0.7 UI primitives

F0.8 formatting
F0.9 API client/OpenAPI
F0.10 error contract
F0.11 auth
F0.12 OrganizationContext
F0.13 StoreContext
F0.14 authorization
F0.15 server state
F0.16 forms
F0.17 Admin tables
F0.18 routing
F0.19 Admin shell
F0.20 POS shell
F0.21 notifications
F0.22 idempotency handling
F0.23 network abstraction
F0.24 feature structure
F0.25 tests
F0.26 observability
F0.27 CI
```

---

# 86. Commits atomiques initiaux

```text
build(frontend): initialize frontend workspace
build(admin): initialize Next.js application
build(pos): initialize React Vite Tauri application
build(frontend): configure strict TypeScript
chore(frontend): add code quality tooling

feat(ui): add shared design tokens
feat(ui): add core UI primitives
feat(frontend): add domain formatters

feat(api): add typed frontend API client
feat(api): add frontend error mapping
feat(auth): add frontend session foundation
feat(access): add frontend permission guards
feat(context): add organization context
feat(context): add store context

test(frontend): add foundation integration tests
ci(frontend): add frontend quality pipeline
```

---

# 87. Definition of Done — Frontend Foundation

Le Foundation est `DONE` uniquement lorsque :

```text
[ ] workspace frontend exécutable
[ ] apps/admin séparée
[ ] apps/pos séparée
[ ] packages partagés contrôlés

[ ] Admin utilise Next.js + React + TypeScript
[ ] POS utilise React + Vite + TypeScript
[ ] Tauri POS exécutable
[ ] cible Windows MVP documentée

[ ] TypeScript strict
[ ] lint actif
[ ] formatting actif
[ ] typecheck racine
[ ] imports / boundaries contrôlés

[ ] design tokens partagés
[ ] primitives UI disponibles
[ ] Admin et POS peuvent diverger UX proprement
[ ] accessibility baseline définie

[ ] Money formatter
[ ] Quantity formatter
[ ] date/time formatter
[ ] Decimal API non converti aveuglément en float

[ ] typed API client
[ ] OpenAPI generation spike validé
[ ] generated code isolé
[ ] HTTP transport centralisé
[ ] correlation ID supporté
[ ] error contract mappé

[ ] authentication bootstrap
[ ] JWT lifecycle
[ ] refresh rotation compatible
[ ] concurrent refresh contrôlé
[ ] logout
[ ] expiration session
[ ] token secrets jamais loggés

[ ] OrganizationContext
[ ] StoreContext
[ ] organization switch sécurisé
[ ] store switch sécurisé
[ ] cache tenant-aware

[ ] EffectiveAccess depuis serveur
[ ] permission helpers
[ ] store scopes
[ ] aucun check brut de rôle comme règle métier
[ ] backend reste autorité d’autorisation

[ ] server state séparé du UI state
[ ] query keys tenant/store scoped
[ ] cache invalidation lors changement contexte

[ ] forms foundation
[ ] backend validation errors supportés
[ ] dirty/submission state standardisé

[ ] Admin table foundation
[ ] server pagination prête
[ ] filters/search conventions

[ ] Admin Shell
[ ] POS Shell
[ ] protected routing
[ ] permission-aware navigation

[ ] idempotency key support
[ ] unknown mutation outcome traité
[ ] retry même intention conserve la clé

[ ] unit tests
[ ] component tests
[ ] integration tests
[ ] auth E2E
[ ] organization/store E2E
[ ] permission/scope E2E

[ ] frontend observability foundation
[ ] aucune donnée sensible dans logs
[ ] frontend version identifiable

[ ] Admin build réussi
[ ] POS build réussi
[ ] Tauri check/build validé selon CI disponible
[ ] lint vert
[ ] format check vert
[ ] typecheck vert
[ ] tests verts
[ ] CI verte

[ ] aucune logique métier backend dupliquée
[ ] aucun SQLite métier offline prématuré
[ ] aucun Reporting fictif
[ ] aucune dépendance directe UI → Tauri partout
[ ] aucun secret frontend

[ ] ADR mis à jour pour tout choix structurant
```

---

# 88. Gate de démonstration

La démonstration Foundation doit prouver :

```text
1. démarrage Admin
2. démarrage POS navigateur
3. démarrage POS Tauri

4. login valide
5. login invalide
6. refresh access token
7. logout

8. récupération effective access
9. Organization sélectionnée
10. Store sélectionné

11. route autorisée visible
12. route interdite cachée/guardée
13. backend DENIED géré proprement

14. appel OpenAPI typé
15. business error transformée en message UI
16. correlationId visible pour diagnostic

17. changement Store
18. cache isolé par Organization/Store

19. mutation critique avec Idempotency-Key
20. timeout/retry même intention sans nouvelle clé

21. composant UI partagé rendu dans Admin
22. même primitive rendue dans POS

23. tests / typecheck / lint / builds verts
```

---

# 89. Premier vertical slice après Foundation

Recommandation :

```text
Admin — Stores
```

Ordre :

```text
Store list
→ Store details
→ Create Store
→ Update Store
→ Suspend Store
→ Reactivate Store
→ Request closure
```

Ce slice valide :

```text
auth
organization context
store scope
permissions
forms
query
mutation
errors
tables
routing
operational status
```

sans commencer par une feature trop complexe.

---

# 90. Deuxième vertical slice Admin

```text
Users / Memberships / Roles
```

Puis :

```text
Catalog
Pricing
Inventory
Purchasing
Cash
```

---

# 91. Premier vertical slice POS

Après le shell :

```text
CashRegister
→ OpenCashSession
```

Puis :

```text
Product search
→ Cart
→ CompleteSale cash
→ Receipt
```

Ce parcours donne rapidement un POS utilisable en online.

---

# 92. Séquence produit recommandée

```text
Frontend Foundation
        ↓
Admin Slice 1 — Stores
        ↓
Admin Slice 2 — Users / Access
        ↓
Admin Slice 3 — Catalog / Pricing
        ↓
Admin Slice 4 — Inventory
        ↓
Admin Slice 5 — Purchasing
        ↓
Admin Slice 6 — Cash Management
        ↓
POS Foundation validation
        ↓
POS Cash Session
        ↓
POS Sale / CompleteSale cash
        ↓
POS Returns
```

Les écrans peuvent ensuite évoluer pendant la reprise des Lots backend 8+.

---

# 93. Principe directeur

Le Frontend Foundation ne doit pas devenir un deuxième projet d’architecture interminable.

Sa réussite se mesure à une chose :

> permettre à une feature réelle de traverser proprement Login → Authorization → API → UI → Mutation → Error handling → Tests.

Dès que le vertical slice `Stores` prouve cette chaîne, il faut arrêter d’élargir le Foundation et commencer à livrer les interfaces métier.
