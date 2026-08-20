# Zandu Sales Manager — Suivi d’implémentation

Ce document suit l’avancement réel de l’implémentation de **Zandu Sales Manager** à partir du **Lot 0 — Architecture exécutable**.

Il ne remplace ni la spécification DDD, ni les ADR, ni le backlog du Lot 0.
Son rôle est de conserver une trace simple de ce qui a effectivement été réalisé dans le repository.

---

## Références

- Spécification d’architecture DDD v1.1
- ADR techniques 0001–0013
- `zandu-lot-0-architecture-executable.md`

---

# Epic 0.1 — Initialisation du repository backend

## 0.1.1 — Initialiser Symfony

**Statut : TERMINÉ**

### Réalisé

- repository Git initialisé à la racine de `zandu-sales-manager/` ;
- backend Symfony créé dans `backend/` ;
- Symfony `7.4.16 LTS` installé ;
- environnement PHP local vérifié avec PHP `8.5.4` ;
- configuration `dev` fonctionnelle ;
- configuration `test` fonctionnelle ;
- PHPUnit opérationnel ;
- test minimal du Kernel ajouté ;
- `composer validate --no-check-publish` retenu pour valider l’application sans imposer les métadonnées d’un package publiable ;
- aucune logique métier introduite ;
- Doctrine, API Platform et PostgreSQL non introduits à cette étape.

### Validations exécutées

```bash
php bin/phpunit
```

Résultat :

```text
OK (1 test, 1 assertion)
```

```bash
php bin/console about
```

Résultat : Symfony démarre correctement en environnement `dev`.

```bash
APP_ENV=test php bin/console about
```

Résultat : Symfony démarre correctement en environnement `test`.

```bash
composer validate --no-check-publish
```

### Commit atomique

```text
build(backend): initialize Symfony application
```

---

## Documentation d’architecture

**Statut : EN COURS**

Le dossier suivant a été ajouté au repository :

```text
docs/
└── architecture/
    └── adr/
```

Il contient les ADR techniques initiaux.

### Commit recommandé

```text
docs(adr): add initial architecture decisions
```

La spécification DDD v1.1 et le backlog du Lot 0 doivent encore être ajoutés dans `docs/`.

Structure cible proposée :

```text
docs/
├── architecture/
│   ├── ddd/
│   │   └── zandu-sales-manager-ddd-v1.1.docx
│   └── adr/
│       └── ...
└── planning/
    └── zandu-lot-0-architecture-executable.md
```

Commits recommandés lors de leur ajout :

```text
docs(architecture): add DDD architecture specification
docs(planning): add Lot 0 implementation backlog
```

---

## 0.1.2 — Définir la structure racine DDD

**Statut : EN COURS**

Structure prévue :

```text
backend/src/
├── Modules/
├── SharedKernel/
├── Platform/
└── Kernel.php
```

Les dossiers vides peuvent temporairement être conservés avec `.gitkeep`.

À cette étape :

- ne pas encore créer `Sales`, `Inventory` ou `CashManagement` ;
- ne pas encore modifier les namespaces Composer ;
- ne pas encore installer Doctrine ;
- ne pas encore ajouter PostgreSQL.

### Commit atomique prévu

```text
refactor(architecture): introduce modular monolith root structure
```

---

# Prochaines étapes

## 0.1.3 — Ajouter les premiers bounded contexts

À créer :

```text
backend/src/Modules/
├── Sales/
├── Inventory/
└── CashManagement/
```

avec, pour chaque module :

```text
Domain/
Application/
└── Contract/
Infrastructure/
Presentation/
└── Api/
```

Commit prévu :

```text
refactor(modules): add initial bounded context skeletons
```

## 0.1.4 — Configurer les namespaces

Namespaces cibles :

```text
Zandu\\Modules\\...
Zandu\\SharedKernel\\...
Zandu\\Platform\\...
```

Commit prévu :

```text
build(autoload): configure Zandu namespaces
```

---

# Règle de mise à jour

Mettre à jour ce fichier après chaque étape validée.

Pour chaque étape :

1. passer son statut à `TERMINÉ` ;
2. documenter uniquement ce qui a réellement été réalisé ;
3. noter les validations exécutées ;
4. noter le commit atomique correspondant ;
5. ajouter l’étape suivante sans la marquer comme terminée avant validation.

Ce fichier doit refléter l’état réel du repository et non l’état prévu du backlog.
