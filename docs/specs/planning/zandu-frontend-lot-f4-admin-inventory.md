# Zandu Frontend — Lot F4 : Admin Inventory

**Version :** 1.0  
**Statut :** Prêt à implémenter — Gate F3 en attente de validation Chromium CI  
**Langue :** Français — identifiants de code en anglais

## 1. Objectif

F4 rend administrables les capacités Inventory déjà disponibles côté backend :
positions et mouvements de stock, initialisation et ajustements, valorisation,
transferts inter-store et inventaires physiques.

À la sortie du Lot, un acteur autorisé peut répondre depuis l’Admin à quatre
questions : quelle quantité est disponible, pourquoi elle a changé, quelle
valeur économique lui est associée, et quels workflows de transfert ou de
comptage sont en cours.

## 2. Position dans la roadmap

```text
Frontend Foundation → F1 Stores → F2 Access → F3 Catalog/Pricing
→ F4 Admin Inventory
```

F4 dépend des capacités backend Inventory/Inventory Costing et des contrats
Catalog/Store déjà exposés. Il ne dépend pas des numéros de Lots.

## 3. Frontières métier

- `Inventory` possède la quantité physique et son ledger.
- `InventoryCosting` possède la valeur économique et son ledger.
- `Catalog` fournit les métadonnées Produit ; F4 ne modifie pas Product.
- `Organization` fournit les stores ; le store actif n’est jamais un tenant.
- Le frontend orchestre l’UX et appelle les contrats serveur sans réimplémenter
  les invariants de stock, de coût, de concurrence ou d’autorisation.

## 4. Périmètre

- positions `Stock` par store et produit ;
- initialisation unique et ajustement justifié ;
- historique append-only `StockMovement` ;
- visibilité `StockValuation` et mouvements de valorisation ;
- cycle `StockTransfer` DRAFT → SHIPPED → RECEIVED ou CANCELLED ;
- cycle `StockCount`, saisie, mode BLIND et finalisation ;
- permissions, scopes store, erreurs, cache, accessibilité et tests.

## 5. Hors périmètre

- modification ou suppression d’un ledger ;
- stock négatif, réservations et warehouse management avancé ;
- lot tracking, numéros de série et péremption ;
- règles métier calculées dans Next.js ;
- exposition des coûts sans permission dédiée ;
- UX POS ou offline.

## 6. Invariants transverses

- toutes les quantités, coûts et valeurs restent des chaînes décimales exactes ;
- `quantityOnHand >= 0` est une autorité serveur ;
- un mouvement historique n’est jamais édité ou supprimé ;
- organisation active et scope Store sont vérifiés à chaque lecture/mutation ;
- `Stock` et `StockValuation` restent deux projections distinctes ;
- aucune valeur économique n’est déduite d’un prix de vente ;
- les réponses cross-tenant sont projetées comme absentes ;
- les erreurs conservent `correlationId` et codes métier ;
- les listes conservent l’ordre/pagination serveur ;
- les mutations invalidantes rafraîchissent toutes les projections dépendantes.

## 7. Contrats Stock

```text
GET  /api/stores/{storeId}/stocks
GET  /api/stores/{storeId}/stocks/{productId}
POST /api/stores/{storeId}/stocks/{productId}/initialize
POST /api/stores/{storeId}/stocks/{productId}/adjust
```

Initialiser envoie `quantity` et `unitCost` exacts. Ajuster envoie un `delta`
non nul, une justification et `unitCost` uniquement pour une entrée lorsque le
contrat l’exige. Les sorties n’acceptent jamais un coût choisi par le client.

## 8. Contrats StockMovement

```text
GET /api/stores/{storeId}/stock-movements
GET /api/stores/{storeId}/stocks/{productId}/movements
```

Les mouvements exposent type, quantité absolue, quantité précédente/résultante,
source, raison et date. Aucun endpoint PATCH/DELETE n’est attendu.

## 9. Contrats de valorisation

```text
GET  /api/stores/{storeId}/inventory-valuations
GET  /api/stores/{storeId}/inventory-valuations/{productId}
GET  /api/stores/{storeId}/inventory-valuations/{productId}/movements
POST /api/stores/{storeId}/inventory-valuations/{productId}/initialize
```

L’initialisation d’une position historique est explicite et justifiée. Quantité
nulle implique valeur totale nulle. Dans le contrat serveur actuel, coût moyen
et valeur totale sont lisibles avec `INVENTORY_READ`. Une séparation plus fine
de leur visibilité exige d’abord une évolution explicite du contrat backend.

## 10. Contrats StockTransfer

Le frontend utilise les endpoints collection/item, lignes DRAFT, `ship`,
`receive` et `cancel`. Source et destination sont distinctes et du même tenant.
Les trois quantités requested/shipped/received ne sont jamais fusionnées.
L’autorisation multi-store est décidée par le serveur.

## 11. Contrats StockCount

Le frontend utilise collection/item, création par store, `start`, saisie simple
ou batch, `finalization` et `cancel`. En mode BLIND, aucune quantité attendue ni
variance n’est affichée si le serveur ne les expose pas. `null` reste distinct
de zéro compté.

## 12. Store context et navigation

Les routes protégées utilisent `/admin/inventory/...`. La surface propose
Positions, Mouvements, Valorisation, Transferts et Inventaires selon les
permissions. Les écrans store-scoped exigent un store actif accessible et
purge/refetch leurs caches lors d’un changement.

## 13. Permissions

F4 reflète les permissions réelles `INVENTORY_*`, `STOCK_MOVEMENT_READ`,
`STOCK_TRANSFER_*`, `STOCK_COUNT_*`, `INVENTORY_COSTING_INITIALIZE` et
`INVENTORY_COST_ASSIGN`. Le contrat actuel utilise `INVENTORY_READ` pour la
lecture des valorisations et ne définit ni `INVENTORY_COST_VIEW` ni
`INVENTORY_VALUE_VIEW` ; le frontend ne les invente pas. Les guards UI sont
ergonomiques ; le serveur reste l’autorité.

## 14. Erreurs et concurrence

Les conflits de version, stock insuffisant, stock non initialisé, transfert non
éditable, conflit de snapshot et produit verrouillé sont rendus sans masquer le
code ni la corrélation. Une mutation à résultat inconnu n’est pas rejouée
aveuglément. Les transitions dédiées conservent leur stratégie serveur.

## 15. Cache et invalidation

Les clés incluent organisation, store, ressource, filtres et version
d’autorisation. Initialisation/ajustement invalident position, mouvements et
valorisation. Expédition/réception/finalisation invalident également les stores
et produits concernés.

## 16. Accessibilité et responsive

Navigation clavier, labels explicites, tableaux lisibles, focus après mutation,
confirmations accessibles et annonces des erreurs sont obligatoires. Desktop
reste prioritaire ; les actions critiques demeurent utilisables sur petit écran.

## 17. Observabilité

La frontière API centralisée transporte authentification et corrélation. Les
opérations portent `feature=inventory`, une opération stable et la route Admin,
sans quantité, coût, raison libre ou identifiant sensible dans les attributs.

## 18. Stratégie de tests

- unitaires : formatage et validation structurelle sans calcul flottant ;
- composants : permissions, états, formulaires et mode BLIND ;
- intégration : contrats HTTP, tenant/store projection, exactitude et erreurs ;
- E2E : positions/mouvements, transfert, inventaire ;
- lint, format, typecheck, builds et architecture restent obligatoires.

## 19. Epic F4.1 — Inventory feature foundation

Créer la frontière `features/inventory` et les contrats typés de lecture Stock /
StockMovement. Décoder strictement les réponses, préserver les décimaux et
projeter organisation/store. Aucun écran métier dans cet Epic.

## 20. Epic F4.2 — Inventory navigation and store context

Créer les routes/sous-navigation permission-aware et exiger un store actif.

## 21. Epic F4.3 — Stock positions workspace

Afficher et filtrer côté serveur les positions, enrichies par les métadonnées
Catalog sans dépendance interne entre features.

## 22. Epic F4.4 — Stock position details

Afficher quantité, état d’initialisation, version et liens vers mouvements et
valorisation, avec projection tenant/store défensive.

## 23. Epic F4.5 — Initialize stock

Formulaire exact, coût d’ouverture explicite, résultat inconnu non rejoué et
invalidation coordonnée.

## 24. Epic F4.6 — Adjust stock

Delta signé, raison obligatoire, coût seulement pour entrée selon contrat et
erreurs d’insuffisance visibles.

## 25. Epic F4.7 — Stock movement history

Historique append-only, filtres serveur, sens dérivé du type et pagination
serveur lorsqu’elle est disponible.

## 26. Epic F4.8 — Inventory valuation views

Contrats et vues de valorisation/ledger, montants exacts et état non initialisé.

## 27. Epic F4.9 — Cost confidentiality

Protéger coûts, valeurs et attributions selon les permissions contractuelles, y
compris dans export, erreurs, skeletons et mode BLIND. La lecture repose
actuellement sur `INVENTORY_READ` ; toute permission de visibilité plus fine
nécessite d’abord une évolution backend.

## 28. Epic F4.10 — Stock transfer list and details

Lister et détailler stores, statuts, lignes et écarts sans inventer de stock en
transit physique.

## 29. Epic F4.11 — Create and edit draft transfer

Créer source/destination, gérer les lignes DRAFT et transmettre expectedVersion
pour les éditions interactives.

## 30. Epic F4.12 — Ship transfer

Saisir les quantités expédiées, confirmer, gérer insuffisance/idempotence et
rafraîchir les stocks concernés.

## 31. Epic F4.13 — Receive transfer and discrepancy

Saisir la réception finale, conserver les écarts et ne jamais créer la quantité
manquante.

## 32. Epic F4.14 — Cancel transfer and multi-store scope

Annuler uniquement DRAFT avec raison et représenter les refus de scope sur les
deux stores.

## 33. Epic F4.15 — Stock count list and details

Lister et détailler scope, mode, statut, progression et lignes selon le contrat.

## 34. Epic F4.16 — Create and start stock count

Créer FULL/PARTIAL, choisir les produits pour PARTIAL puis ouvrir le snapshot.

## 35. Epic F4.17 — Record counts

Saisie simple/batch exacte, zéro explicite, contrôle de version de ligne et
collaboration concurrente sûre.

## 36. Epic F4.18 — Blind count UX

Ne jamais reconstruire ou révéler expectedQuantity/variance absents du serveur.

## 37. Epic F4.19 — Finalize, recover and cancel stock count

Déclencher la finalisation, suivre la progression/reprise et annuler uniquement
aux états permis, sans boucle métier côté navigateur.

## 38. Epic F4.20 — Security, errors and cache coherence

Consolider permissions, codes métier, corrélation, invalidations et changement
de tenant/store sur toutes les surfaces.

## 39. Epic F4.21 — Accessibility and responsive quality

Auditer navigation, formulaires, tableaux, dialogues, annonces et petit écran.

## 40. Epic F4.22 — Unit, component and integration tests

Couvrir contrats, exactitude, permissions, BLIND, erreurs et invalidations.

## 41. Epic F4.23 — E2E Inventory flows

Trois parcours : positions/mouvements, transfert et inventaire physique.

## 42. Gate F4

Le Gate exige notamment : navigation et store context ; positions et mouvements
lisibles ; initialisation/ajustement exacts ; confidentialité des coûts ; cycle
transfert complet ; cycle comptage complet avec BLIND ; erreurs corrélées ;
tenant/store isolation ; tests unitaires/composants/intégration ; trois E2E ;
lint, format, typecheck et build Admin verts.

## 43. Dépendances explicites

- API backend Stock/StockMovement/InventoryCosting/StockTransfer/StockCount ;
- Catalog Product read contract ;
- Stores accessibles et StoreContext Foundation ;
- session, permissions, API client, cache, forms, tables et notifications ;
- ADR-0005, 0006, 0008, 0009, 0013, 0015, 0017, 0018, 0021, 0024, 0025.

## 44. Premier point d’entrée

Commencer par F4.1 : types/décodeurs/méthodes de lecture Stock et StockMovement,
tests d’intégration et frontière publique Inventory. Ne pas construire la
navigation ni les mutations avant que ces contrats soient stables.
