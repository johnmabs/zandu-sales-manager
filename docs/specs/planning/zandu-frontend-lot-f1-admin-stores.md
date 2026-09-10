# Zandu Sales Manager — Frontend Lot F1 : Admin Stores

**Version :** 1.0  
**Statut :** Backlog d’implémentation  
**Surface :** Zandu Admin  
**Langue :** Français — identifiants de code en anglais

---

# 1. Objectif du Lot F1

Le Lot F1 constitue le premier vertical slice métier livré après le `Frontend Foundation`.

Il doit transformer le socle frontend en une première capacité réellement utilisable :

```text
Administration des magasins
```

Le parcours cible est :

```text
Login
→ Organization context
→ Store list
→ Store details
→ Create Store
→ Update Store
→ Suspend Store
→ Reactivate Store
→ Request Store Closure
```

Ce choix suit directement la recommandation du `Frontend Foundation`.

Le Lot F1 ne doit pas simplement produire quelques écrans. Il doit prouver qu’une feature réelle traverse correctement :

```text
Authentication → Authorization → Organization context → Routing
→ Generated/API contracts → Server state → Forms → Mutation
→ Business errors → Permission handling → Loading states → Tests
```

Le succès de F1 constitue donc également une validation pratique du Frontend Foundation.

# 2. Position dans la roadmap frontend

```text
Frontend Foundation F0.1 → F0.27
        ↓
Gate F0
        ↓
Lot F1 — Admin Stores
        ↓
Lot F2 — Users / Memberships / Roles
        ↓
Lot F3 — Catalog / Pricing
```

Le Foundation recommande explicitement de commencer par `Stores`, puis de poursuivre avec `Users / Memberships / Roles`.

# 3. Pourquoi commencer par Stores

`Stores` constitue un premier slice suffisamment réel pour tester l’architecture frontend, sans introduire immédiatement la complexité du catalogue, du stock ou des ventes.

Le slice implique déjà :

```text
authentication
organization context
store scopes
permissions
routing
lists
details
forms
queries
mutations
business errors
operational lifecycle
```

Il couvre donc pratiquement toutes les primitives importantes mises en place par le Foundation.

# 4. Source de vérité métier

DÉCIDÉ

```text
Symfony backend = source of truth
```

Le frontend affiche l’état, collecte l’intention utilisateur, prépare les requests, protège l’UX selon les permissions connues, affiche les transitions disponibles et gère les états de chargement et d’erreur.

Il ne doit pas reproduire les invariants métier du backend, décider qu’une fermeture est possible, calculer lui-même les blockers, considérer qu’une permission UI constitue une autorisation, ni reconstruire un aggregate `Store` côté client.

# 5. Capacités backend disponibles

Le backend fournit déjà :

```text
GET    /api/stores
POST   /api/stores
GET    /api/stores/{id}
PATCH  /api/stores/{id}

+ transitions explicites :
Suspend Store
Reactivate Store
Request Store Closure
```

La réponse de fermeture expose également :

```text
closure status
+
blockers
```

Les lectures sont protégées par `STORE_READ` et utilisent les scopes tenant/store déjà définis.

Le modèle backend dispose déjà des use cases `CreateStore`, `UpdateStore`, `SuspendStore`, `ReactivateStore`, `RequestStoreClosure`, `CancelStoreClosure`, ainsi que du process manager :

```text
StoreClosure

REQUESTED
IN_PROGRESS
READY
COMPLETED
CANCELLED
```

Le frontend doit consommer les contrats API existants plutôt que reconstruire ces concepts.

# 6. Règles Store importantes pour l’UX

Le modèle Store possède notamment :

```text
StoreId
OrganizationId
StoreCode
StoreName
StoreAddress
currency
timeZone
status
version
```

Le code et l’identité sont immuables après création. La devise du magasin est contrainte par la devise par défaut de l’organisation.

Conséquence frontend :

```text
Create Store → StoreCode editable
Update Store → StoreCode read-only
```

Le frontend ne doit pas présenter une modification du code comme une opération disponible si le contrat backend ne la supporte pas.

# 7. Lifecycle opérationnel

Le frontend doit représenter les transitions métier disponibles sans inventer de lifecycle parallèle.

Les opérations actuellement supportées sont notamment :

```text
Create
Update
Suspend
Reactivate
Request Closure
```

Le backend possède également un workflow de fermeture séparé du simple statut du Store. L’UX doit donc distinguer `Store lifecycle` de `StoreClosure process`.

Une demande de fermeture n’est pas équivalente à `store.status = CLOSED`.

# 8. Principe de permissions

Le frontend utilise les permissions récupérées par le Foundation afin de masquer les actions manifestement indisponibles, désactiver certaines commandes lorsque pertinent et empêcher l’accès UX évident à une route interdite.

Cependant :

```text
UI PermissionGate ≠ security boundary
```

Toute mutation reste autorisée ou rejetée côté serveur. Un rejet serveur doit toujours être traité correctement, même lorsque le frontend pensait l’action disponible.

Le backend possède déjà une autorisation basée sur permission atomique et `ResourceScope`, avec contrôle organisation/store et rejet des contextes cross-tenant.

# 9. Architecture de feature proposée

PROPOSÉ

Dans `apps/admin` :

```text
features/
└── stores/
    ├── api/
    ├── components/
    ├── hooks/
    ├── routes/
    ├── schemas/
    ├── state/
    ├── tests/
    └── index.ts
```

Exemple plus détaillé :

```text
features/stores/
├── api/
│   ├── store-queries.ts
│   └── store-mutations.ts
├── components/
│   ├── StoreTable.tsx
│   ├── StoreStatusBadge.tsx
│   ├── StoreForm.tsx
│   ├── StoreDetails.tsx
│   ├── StoreActions.tsx
│   └── StoreClosureBlockers.tsx
├── schemas/
│   ├── create-store.schema.ts
│   └── update-store.schema.ts
├── routes/
├── tests/
└── index.ts
```

Cette structure reste une proposition d’implémentation. Elle doit respecter les conventions finales établies par F0.24 et les décisions prises pendant la Foundation.

# 10. Epic F1.1 — Stores feature foundation

## Objectif

Créer la frontière frontend de la feature `Stores`, avec un espace clairement identifié pour `queries`, `mutations`, `screens`, `components`, `forms` et `tests`, sans déplacer les primitives réellement génériques hors des packages partagés.

## Contraintes

Interdit :

```text
packages/ui → imports stores feature
packages/api-client → imports apps/admin
```

La feature peut dépendre des packages du Foundation. Les packages bas niveau ne doivent pas dépendre de la feature.

## Validation

```text
feature importable
routing works
architecture constraints pass
no backend domain recreation
```

## Commit proposé

```text
feat(admin): add stores feature foundation
```

# 11. Epic F1.2 — Store list

## Objectif

Créer la page principale `/stores`, qui récupère la collection tenant-scoped exposée par `GET /api/stores`. Le backend garantit déjà une collection scoped au tenant actif et ordonnée de manière stable.

## Informations minimales

PROPOSÉ : afficher au minimum les informations réellement présentes dans le contrat API telles que `name`, `code`, `status`. Les champs supplémentaires doivent venir du contrat OpenAPI réel. Ne pas inventer côté frontend des propriétés non exposées.

## États UX

```text
loading
success with data
success empty
permission denied
network failure
business/API failure
```

## Empty state

```text
Aucun magasin

Créez votre premier magasin pour commencer
à configurer les opérations de votre organisation.

[Créer un magasin]
```

Le bouton n’est affiché que si l’utilisateur dispose de la permission correspondante connue par le frontend.

## Validation

```text
authorized list
empty list
permission denied
network failure
cross-scope inaccessible
```

## Commit proposé

```text
feat(admin): add store list
```

# 12. Epic F1.3 — Store details

## Objectif

Créer `/stores/{storeId}`. La page consomme `GET /api/stores/{id}`.

## Contenu

PROPOSÉ : organiser la page autour de `Store identity`, `Store profile`, `Operational status`, `Available actions` et `Closure status`.

```text
Magasin Pointe-Noire Centre

Code
PNR-CENTRE

Statut
ACTIVE

Adresse
...

Fuseau horaire
Africa/Brazzaville

Devise
XAF
```

Selon état et permissions, proposer `Edit`, `Suspend`, `Reactivate`, `Request Closure`. Ne pas afficher toutes les actions simultanément si elles ne correspondent pas à l’état courant.

## Validation

```text
existing store
not found
cross-tenant hidden/not found
permission denied
stale route id
```

## Commit proposé

```text
feat(admin): add store details
```

# 13. Epic F1.4 — Create Store

Créer le formulaire de création utilisant `POST /api/stores`. Les champs exacts doivent être dérivés du contrat OpenAPI. Le frontend peut appliquer des validations UX évidentes (`required field`, format, longueurs exposées), mais les invariants métier restent côté serveur.

Après succès, invalider/refetch la collection, naviguer vers le détail et afficher un feedback de succès.

Gérer explicitement : erreur de validation, `StoreCode` dupliqué, permission refusée, restriction opérationnelle de l’organisation, erreur réseau et erreur backend inattendue.

Si l’API exige une `Idempotency-Key`, utiliser la primitive Foundation correspondante. Un retry de la même intention conserve la même clé.

```text
feat(admin): add store creation
```

# 14. Epic F1.5 — Update Store

Permettre la modification des propriétés éditables via `PATCH /api/stores/{id}` avec le contrat de modification distinct du contrat de création.

Règle critique : `StoreCode` est immuable ; il doit être read-only ou absent du formulaire.

```text
Store details → Edit → form initialized from server state
→ PATCH → refetch/invalidate → updated details
```

Si le contrat expose une version ou produit un `409 CONFLICT`, ne pas écraser silencieusement l’état serveur. UX proposée :

```text
Les informations du magasin ont changé depuis leur chargement.

[Recharger les données]
```

```text
feat(admin): add store editing
```

# 15. Epic F1.6 — Suspend Store

Exposer la transition métier explicite de suspension. Ne pas l’implémenter comme un simple `PATCH status=SUSPENDED` si le backend expose une transition dédiée.

L’action est sensible :

```text
Suspendre ce magasin ?

Les nouvelles opérations seront bloquées
tant que le magasin restera suspendu.

[Annuler] [Suspendre]
```

Le backend possède un `StoreOperationalGuard` : un magasin suspendu bloque les nouvelles opérations tout en permettant certaines actions de remédiation ou terminaison.

Après succès : rafraîchir Store et la liste en cache, mettre à jour `StoreContext` si nécessaire et afficher un feedback de succès.

```text
feat(admin): add store suspension
```

# 16. Epic F1.7 — Reactivate Store

Permettre la réactivation d’un Store suspendu uniquement lorsque l’état courant le permet et que la permission frontend est connue. Le serveur reste l’autorité finale.

Après succès : rafraîchir le statut et `StoreContext` si affecté, puis invalider le cache de liste.

```text
feat(admin): add store reactivation
```

# 17. Epic F1.8 — Request Store Closure

Permettre à l’utilisateur autorisé de demander la fermeture d’un magasin. La fermeture est un workflow dédié `StoreClosure`, non un changement direct du statut Store.

Le backend possède actuellement :

```text
REQUESTED
IN_PROGRESS
READY
COMPLETED
CANCELLED
```

UX initiale :

```text
Request Store Closure → Confirmation → API
→ StoreClosure response → status + blockers
```

# 18. Epic F1.9 — Store Closure blockers

Afficher clairement pourquoi une fermeture ne peut pas encore être finalisée. Le backend retourne les blockers ; le frontend ne doit pas les recalculer.

```text
Backend → blocker codes → frontend presentation mapping
```

Exemple :

```text
Fermeture impossible pour le moment

Des opérations doivent encore être terminées :

• session de caisse ouverte
• inventaire en cours
• commande fournisseur ouverte
```

Les textes précis doivent être associés aux codes réellement exposés par l’API. La liste peut évoluer ; un blocker inconnu doit produire un message générique sûr sans crash.

# 19. Epic F1.10 — Cancel Store Closure

Le backend possède `CancelStoreClosure`. Inclure l’annulation dans F1 si l’endpoint est exposé par le contrat HTTP actuel ; sinon différer jusqu’à exposition API explicite. Ne pas inventer un endpoint frontend.

# 20. Epic F1.11 — Permission-aware actions

Toutes les actions Store utilisent les primitives du Foundation. Les noms réels doivent correspondre au catalogue de permissions généré ou partagé par le projet.

```tsx
<PermissionGate permission="STORE_UPDATE">
  ...
</PermissionGate>
```

Cas à couvrir : permission autorisée, indisponible dans l’état client, refusée localement, refusée par le backend malgré une action visible, et Store hors du scope courant. Gérer proprement le `403` serveur.

# 21. Epic F1.12 — Store context synchronization

Certaines mutations peuvent affecter le Store sélectionné : suspension, réactivation ou indisponibilité. Éviter un `StoreContext` obsolète.

PROPOSÉ :

```text
mutation
→ invalidate Store
→ invalidate Store list
→ refresh active StoreContext
→ reevaluate route/action access
```

Si le Store actif n’est plus opérationnel, présenter un état explicite plutôt que poursuivre silencieusement.

# 22. Epic F1.13 — Loading, empty and error states

Chaque écran dispose d’états explicites. Éviter l’écran vide et prévoir page/table skeleton et boutons en pending. Les listes vides expliquent ce qui est vide, pourquoi cela importe et ce que l’utilisateur peut faire.

Utiliser le contrat d’erreurs Foundation :

```text
ApiError
├── code
├── message
└── correlationId
```

Le `correlationId` reste disponible pour le diagnostic conformément au Gate Foundation.

# 23. Epic F1.14 — Mutation safety

Les mutations sensibles (`Create Store`, `Suspend Store`, `Reactivate Store`, `Request Closure`) empêchent les doubles soumissions. Pendant la mutation : désactiver l’action, afficher l’état pending et préserver l’intention.

En cas de timeout, ne pas supposer l’échec. Utiliser l’idempotence du Foundation lorsque l’endpoint le permet.

# 24. Epic F1.15 — Responsive Admin UX

Zandu Admin reste orientée desktop, avec la priorité `desktop → laptop → tablet usable`. Le POS possède sa propre surface et ne doit pas dicter l’UX Admin.

# 25. Epic F1.16 — Accessibility

Respecter les conventions Foundation : navigation clavier, focus visible, labels sémantiques, formulaires et dialogues accessibles, statut non transmis uniquement par la couleur et association des erreurs aux champs. Les actions sensibles doivent être utilisables sans souris.

# 26. Epic F1.17 — Tests unitaires et composants

Tests ciblés sur :

```text
StoreStatusBadge
Store actions availability
CreateStore form validation
UpdateStore immutable fields
StoreClosure blocker mapping
permission rendering
API error mapping
```

Les règles métier serveur ne doivent pas être recopiées dans ces tests.

# 27. Epic F1.18 — Integration tests

Couvrir au minimum :

```text
Store list loads
Store details loads
Create Store succeeds
Create Store validation fails
Update Store succeeds
Suspend Store succeeds
Reactivate Store succeeds
Request Closure succeeds
Closure blockers displayed
permission denied handled
network error handled
```

# 28. Epic F1.19 — E2E vertical slice

Le test principal prouve :

```text
Login → Organization context → Store list → Create Store
→ Store details → Update Store → Suspend Store
→ Reactivate Store → Request Closure
```

Un second scénario couvre :

```text
User without permission → route or action protected
→ backend denial handled → no corrupted UI state
```

# 29. Epic F1.20 — Observability

Les erreurs utilisent les primitives Foundation et incluent `route`, `operation`, `correlationId`, `client version`, `environment` et une catégorie d’erreur sûre.

Ne jamais journaliser : access token, refresh token, password, sensitive headers ou raw authorization data.

# 30. Routing proposé

```text
/admin/stores
/admin/stores/new
/admin/stores/{storeId}
/admin/stores/{storeId}/edit
```

PROPOSÉ : les transitions métier (`Suspend`, `Reactivate`, `Request Closure`) restent de préférence des actions depuis la page détail plutôt que des routes autonomes.

# 31. Écrans du Lot

Minimum attendu : `StoreListPage`, `StoreDetailsPage`, `CreateStorePage`, `EditStorePage`, plus `SuspendStoreDialog`, `ReactivateStoreDialog`, `RequestStoreClosureDialog`, `StoreClosureStatus` et `StoreClosureBlockers`.

# 32. Hors scope F1

Ne pas introduire : Users administration, Memberships, Roles editor, Catalog, Pricing, Inventory, Purchasing, Cash management screens, Sales, POS, Customers, Reporting ou Offline.

Même si certaines informations apparaissent comme blockers de fermeture, F1 ne doit pas implémenter les interfaces métier de ces modules.

# 33. Dépendances

F1 dépend de la réussite du Gate Frontend Foundation, notamment : Admin bootstrap, authentication, refresh/logout, authorization, `OrganizationContext`, `StoreContext`, typed API client, error handling, routing, forms foundation, server state, mutation strategy, idempotency support, tests, observability et CI.

# 34. Ordre d’implémentation recommandé

```text
F1.1  Stores feature foundation
F1.2  Store list
F1.3  Store details
F1.4  Create Store
F1.5  Update Store
F1.6  Suspend Store
F1.7  Reactivate Store
F1.8  Request Store Closure
F1.9  Closure blockers
F1.10 Cancel closure if API available
F1.11 Permission-aware actions
F1.12 StoreContext synchronization
F1.13 Loading / error / empty states
F1.14 Mutation safety
F1.15 Responsive UX
F1.16 Accessibility
F1.17 Unit/component tests
F1.18 Integration tests
F1.19 E2E vertical slice
F1.20 Observability
```

# 35. Stratégie de commits

```text
feat(admin): add stores feature foundation
feat(admin): add store list
feat(admin): add store details
feat(admin): add store creation
feat(admin): add store editing
feat(admin): add store suspension
feat(admin): add store reactivation
feat(admin): add store closure request
feat(admin): display store closure blockers
feat(admin): synchronize active store state
test(admin): cover store administration flows
test(admin): verify store vertical slice
```

Conserver des commits atomiques.

# 36. Gate F1

Le Lot F1 est terminé lorsque la démonstration suivante réussit :

```text
1. User logs in
2. Organization context resolves
3. Store list loads
4. Store details load
5. Store can be created
6. Created Store appears in list
7. Store can be updated
8. immutable fields remain protected
9. Store can be suspended
10. Store can be reactivated
11. closure can be requested
12. closure status is displayed
13. blockers are displayed from backend response
14. permission-denied case behaves correctly
15. store-scope restriction behaves correctly
16. loading state is correct
17. empty state is correct
18. API business error is presented safely
19. network error is recoverable
20. correlationId remains accessible
21. StoreContext does not remain stale after mutations
22. no business invariant is duplicated in frontend
23. unit/component tests pass
24. integration tests pass
25. E2E vertical slice passes
26. lint passes
27. typecheck passes
28. Admin build passes
```

# 37. Résultat attendu

À la sortie du Lot F1, `Zandu Admin` possède sa première capacité métier complète et réellement utilisable : `Store Administration`.

Le Lot doit prouver que le Frontend Foundation permet une feature de traverser correctement :

```text
Login → Authorization → Organization → API → Query → UI → Form
→ Mutation → Business error → Context refresh → Tests
```

sans reconstruire un domaine métier côté frontend.

# 38. Étape suivante

Après validation du Gate F1 :

```text
Frontend Lot F2
Admin Users & Access
```

Périmètre attendu : Organization Invitations, Users / Members, Membership lifecycle, Roles, Role assignments, Organization / Store scopes et Permissions.

Ce Lot exploitera le même socle validé par F1 mais introduira un modèle d’autorisation et d’administration plus riche.
