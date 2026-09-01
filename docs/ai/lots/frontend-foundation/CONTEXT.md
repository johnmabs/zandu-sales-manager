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
