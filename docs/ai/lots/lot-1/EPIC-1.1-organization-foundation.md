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
