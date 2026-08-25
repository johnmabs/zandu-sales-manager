# Fitness tests d’architecture

Ce document décrit les règles d’architecture actuellement rendues exécutables dans **Zandu Sales Manager**.

Les décisions architecturales de référence restent définies dans :

- la spécification d’architecture DDD ;
- les ADR ;
- le backlog du Lot 0.

Les fitness tests ont pour rôle de vérifier automatiquement qu’une modification du code ne viole pas ces décisions.

---

# 1. Outil

Les dépendances architecturales PHP sont analysées avec :

```text
Deptrac
```

Dépendance Composer :

```text
deptrac/deptrac
```

L’analyse est exécutée via :

```bash
make architecture
```

Cette commande fait partie des validations locales et de la CI.

---

# 2. Deux vues architecturales distinctes

L’architecture possède deux dimensions différentes :

1. les couches techniques ;
2. les frontières entre bounded contexts.

Elles sont volontairement analysées par deux configurations Deptrac indépendantes :

```text
backend/
├── deptrac.layers.php
└── deptrac.modules.php
```

Cette séparation évite qu’une même classe appartienne simultanément à une couche technique et à une couche représentant un bounded context dans la même analyse.

---

# 3. Vue des couches techniques

Configuration :

```text
backend/deptrac.layers.php
```

Elle contrôle les dépendances entre :

```text
Domain
Application
Infrastructure
Presentation
SharedKernel
Platform
```

ainsi que les dépendances vers certains frameworks et bibliothèques
techniques :

```text
Symfony
Doctrine
ApiPlatform
BrickMath
OpenTelemetry
Monolog
```

---

## 3.1 Domain

Le domaine constitue le cœur métier.

Dépendance autorisée :

```text
Domain
└── SharedKernel
```

Dépendances notamment interdites :

```text
Domain → Application       ✗
Domain → Infrastructure    ✗
Domain → Presentation      ✗
Domain → Platform          ✗

Domain → Symfony           ✗
Domain → Doctrine          ✗
Domain → ApiPlatform       ✗
```

Le domaine ne doit donc pas dépendre directement d’un framework ou d’un mécanisme d’infrastructure.

---

## 3.2 Application

Dépendances autorisées :

```text
Application
├── Domain
└── SharedKernel
```

L’Application Layer orchestre les use cases mais ne dépend pas directement des détails d’infrastructure ou de présentation.

Dépendances notamment interdites :

```text
Application → Infrastructure
Application → Presentation
Application → Platform
```

Les dépendances cross-context sont traitées séparément par `deptrac.modules.php`.

---

## 3.3 Infrastructure

Dépendances autorisées :

```text
Infrastructure
├── Domain
├── Application
├── SharedKernel
├── Platform
├── Symfony
├── Doctrine
└── ApiPlatform
```

Cette couche porte les implémentations techniques nécessaires aux abstractions définies plus haut dans l’architecture.

---

## 3.4 Presentation

Dépendances autorisées :

```text
Presentation
├── Application
├── SharedKernel
├── Symfony
└── ApiPlatform
```

La présentation appelle l’Application Layer.

Elle ne doit notamment pas accéder directement au Domain ou à Doctrine.

---

## 3.5 SharedKernel

`SharedKernel` contient uniquement les abstractions réellement partagées entre bounded contexts.

Il ne doit pas devenir un emplacement générique utilisé pour contourner les frontières architecturales.

---

## 3.6 Platform

Dépendances actuellement autorisées :

```text
Platform
├── SharedKernel
├── Symfony
├── ApiPlatform
├── BrickMath
├── Doctrine
├── OpenTelemetry
└── Monolog
```

`Platform` héberge les mécanismes techniques transversaux explicitement prévus par l’architecture.

La dépendance vers Symfony permet notamment d’y implémenter les abstractions
techniques du `SharedKernel`, comme la génération UUID v7 prévue par
l’ADR-0007. Elle n’autorise pas `SharedKernel` à dépendre de Symfony.

La dépendance vers BrickMath permet l’implémentation de l’arithmétique décimale
exacte prévue par l’ADR-0008. Les contrats `Decimal`, `DecimalFactory` et
`RoundingMode` restent indépendants de `Brick\Math`.

API Platform et Doctrine sont utilisés par les mécanismes techniques
transversaux de l'API et de la persistence, notamment la décoration OpenAPI,
les transactions tenant et les repositories DBAL partagés. Ils ne deviennent
pas pour autant accessibles à `Domain`, `Application`, `Presentation` ou
`SharedKernel` au-delà des règles propres à chaque couche.

OpenTelemetry et Monolog sont limités à `Platform` pour l'instrumentation et
l'enrichissement des logs prévus par l'ADR-0013. Les modules métier ne doivent
pas dépendre directement de ces fournisseurs techniques.

---

# 4. Vue des bounded contexts

Configuration :

```text
backend/deptrac.modules.php
```

Les bounded contexts actuellement matérialisés sont :

```text
Sales
Inventory
CashManagement
Catalog
IdentityAccess
Organization
Operations
```

Chaque module peut exposer une API applicative publique dans :

```text
Application/Contract/
```

Les layers publics correspondants sont actuellement :

```text
SalesContract
InventoryContract
CashManagementContract
CatalogContract
IdentityAccessContract
OrganizationContract
```

`Operations` ne possède actuellement aucun `Application/Contract` public et
n'autorise aucune dépendance vers un autre bounded context.

---

# 5. Principe de frontière inter-module

Un bounded context ne doit pas dépendre directement de l’implémentation interne d’un autre bounded context.

Interdit :

```text
Sales\Application
        ↓
Inventory\Domain
```

Interdit également :

```text
Sales
        ↓
Inventory\Infrastructure
```

ou :

```text
Sales
        ↓
Inventory\Presentation
```

Une communication inter-module doit passer par une API applicative explicitement publique.

Autorisé :

```text
Sales\Application
        ↓
Inventory\Application\Contract
```

---

# 6. Contracts publics

Un dossier :

```text
Application/Contract/
```

représente la surface applicative publique qu’un bounded context choisit d’exposer aux autres modules.

Exemple :

```text
Inventory
├── Application
│   ├── Contract
│   │   └── ...
│   └── ...
├── Domain
├── Infrastructure
└── Presentation
```

Dans la configuration Deptrac, le contenu de :

```text
Inventory\Application\Contract
```

est exclu du layer interne `Inventory` et appartient au layer :

```text
InventoryContract
```

Conceptuellement :

```text
Inventory
=
tout Zandu\Modules\Inventory\...
SAUF
Zandu\Modules\Inventory\Application\Contract\...
```

Cela permet d’exprimer directement :

```text
Sales → InventoryContract   ✓
Sales → Inventory           ✗
```

---

# 7. Règles cross-context actuellement explicites

`Sales` peut actuellement utiliser :

```text
InventoryContract
CashManagementContract
```

Il ne dispose pas d’un accès général aux modules :

```text
Inventory
CashManagement
```

`IdentityAccess` peut utiliser :

```text
IdentityAccessContract
OrganizationContract
```

`Organization` peut utiliser :

```text
IdentityAccessContract
OrganizationContract
```

`Catalog` peut uniquement utiliser sa propre surface publique :

```text
CatalogContract
```

Il ne peut pas dépendre directement de `Organization`, `IdentityAccess`,
`Inventory`, `Sales` ou `CashManagement`. Les futurs consommateurs du catalogue
devront passer par `CatalogContract` lorsqu'un contrat public concret sera
introduit.

L'accès d'un module à son propre layer `Contract` permet à son implémentation
interne d'implémenter et de consommer ses contrats publics sans réintégrer ces
classes dans le layer interne. Les dépendances réciproques entre
`IdentityAccess` et `Organization` restent limitées à ces surfaces
applicatives ; aucun des deux modules ne peut accéder directement au Domain, à
l'Infrastructure ou à la Presentation de l'autre.

`Inventory`, `CashManagement`, `Operations` et tous les layers `Contract`
n'autorisent actuellement aucune dépendance inter-module supplémentaire.

L’ajout d’une nouvelle dépendance inter-module doit être explicite dans `deptrac.modules.php`.

Il est interdit d’ajouter une autorisation globale uniquement pour faire disparaître une violation Deptrac.

---

# 8. Validation locale

Exécuter toutes les règles d’architecture :

```bash
make architecture
```

La commande exécute les deux analyses :

```bash
vendor/bin/deptrac analyse \
  --config-file=deptrac.layers.php \
  --no-cache
```

et :

```bash
vendor/bin/deptrac analyse \
  --config-file=deptrac.modules.php \
  --no-cache
```

Une exécution valide doit terminer sans violation.

---

# 9. Validation avec les autres contrôles backend

Avant un commit significatif :

```bash
make lint
make test
make architecture
```

Ces commandes couvrent des responsabilités différentes :

```text
make lint
→ configuration et validations techniques

make test
→ tests automatisés

make architecture
→ règles structurelles et frontières DDD
```

---

# 10. Intégration continue

Le workflow backend GitHub Actions exécute les fitness tests.

Fichier :

```text
.github/workflows/backend-ci.yml
```

La chaîne de validation comprend notamment :

```text
build
→ install
→ lint
→ quality
→ tests
→ architecture
→ security
→ staging
→ backup/restore
```

Une violation Deptrac doit provoquer l’échec de la CI.

---

# 11. Validation de bout en bout réalisée

La protection des frontières inter-bounded-context a été vérifiée avec une violation temporaire :

```text
Sales\Application
        ↓
Inventory\Domain
```

Résultat :

```text
Deptrac
→ violation détectée

make architecture
→ échec

GitHub Actions
→ pipeline rouge
```

Après suppression de la dépendance interdite :

```text
Deptrac
→ aucune violation

make architecture
→ succès

GitHub Actions
→ pipeline verte
```

Les classes utilisées pour ces validations étaient temporaires et ne font pas partie du code métier.

---

# 12. Validation des dépendances framework

Une violation volontaire a été réalisée avec :

```text
Domain
        ↓
Symfony\Component\...
```

Deptrac a correctement rejeté cette dépendance.

État actuel :

```text
Domain → Symfony       configuré et testé
Domain → Doctrine      configuré
Domain → ApiPlatform   configuré
```

Doctrine et API Platform sont désormais présents et utilisés dans
`Infrastructure`, `Presentation` et `Platform` conformément au ruleset. Leur
interdiction depuis `Domain` reste active dans `deptrac.layers.php` et toute
dépendance future dans ce sens doit faire échouer l'analyse.

---

# 13. Ajouter un nouveau bounded context

Lorsqu’un nouveau bounded context devient matérialisé dans le code :

1. créer sa structure DDD ;
2. définir son layer interne dans `deptrac.modules.php` ;
3. définir son `Application\Contract` public si nécessaire ;
4. exclure ce Contract du layer interne du module ;
5. déclarer uniquement les dépendances cross-context réellement autorisées ;
6. exécuter `make architecture` ;
7. vérifier au besoin la nouvelle frontière avec une violation temporaire.

Exemple conceptuel :

```text
Purchasing
PurchasingContract
```

Ne pas créer une autorisation globale telle que :

```text
Sales → Purchasing
```

si seule une API spécifique doit être publique.

---

# 14. Ajouter ou modifier une règle

Toute modification d’une règle Deptrac doit répondre à une nécessité architecturale réelle.

Avant d’assouplir une règle :

1. vérifier que la dépendance est réellement conforme à la baseline ;
2. vérifier qu’un Contract applicatif ne constitue pas une meilleure frontière ;
3. documenter une nouvelle décision architecturale si la baseline change ;
4. modifier ensuite le fitness test.

Une violation Deptrac ne doit jamais être corrigée uniquement en élargissant arbitrairement le ruleset.

---

# 15. Probes architecturaux

Des classes temporaires peuvent être utilisées pour vérifier qu’une règle fonctionne réellement.

Exemple :

```text
InvalidArchitectureProbe
ArchitectureProbe
```

Ces classes :

- servent uniquement aux validations ;
- ne contiennent aucune logique métier ;
- doivent être supprimées après le test ;
- ne doivent pas être intégrées à `main`.

Une règle importante doit idéalement être validée dans les deux sens :

```text
dépendance interdite
→ violation attendue
```

et :

```text
dépendance autorisée
→ aucune violation
```

---

# 16. Principe général

Les fitness tests ne remplacent pas l’architecture.

Ils rendent certaines décisions architecturales automatiquement vérifiables.

La règle générale reste :

```text
architecture décidée
        ↓
règle exécutable
        ↓
validation locale
        ↓
validation CI
```

Une modification qui casse une frontière DDD doit échouer avant son intégration dans `main`.
