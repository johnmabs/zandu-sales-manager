# 6. Epic 0.3 — Persistence foundation

## Objectif

Rendre PostgreSQL et Doctrine utilisables conformément aux décisions d’architecture.

## Étape 0.3.1 — Installer Doctrine ORM, DBAL et Migrations

### Commit proposé

```text
build(persistence): add Doctrine ORM DBAL and migrations
```

---

## Étape 0.3.2 — Configurer PostgreSQL

Configurer :

- connexion ;
- environnement test ;
- conventions de schéma ;
- types nécessaires.

### Commit proposé

```text
chore(persistence): configure PostgreSQL integration
```

---

## Étape 0.3.3 — Créer les schémas initiaux

Créer uniquement les schémas nécessaires aux premiers spikes :

```text
sales
inventory
cash_management
platform
```

### Commit proposé

```text
feat(database): add initial bounded context schemas
```

---

## Étape 0.3.4 — Ajouter `TransactionManager`

Contrat côté application / kernel technique selon l’organisation retenue :

```text
TransactionManager
```

Implémentation :

```text
DoctrineTransactionManager
```

La transaction physique peut coordonner plusieurs bounded contexts du modular monolith sans autoriser l’accès direct à leurs repositories internes.

### Commit proposé

```text
feat(persistence): add application transaction manager
```

---

## Étape 0.3.5 — Définir les conventions de repository

- un repository par aggregate root lorsque nécessaire ;
- pas de `GenericRepository` métier ;
- interfaces définies du côté qui en a besoin ;
- infrastructure Doctrine derrière les interfaces.

### Commit proposé

```text
docs(persistence): define repository conventions
```

---

## Étape 0.3.6 — Ajouter les tests PostgreSQL réels

Les tests d’intégration importants utilisent le même moteur que la production.

### Commit proposé

```text
test(persistence): add real PostgreSQL integration tests
```

---

## Definition of Done — Epic 0.3

- migrations exécutables ;
- PostgreSQL réel utilisé en intégration ;
- transaction explicite fonctionnelle ;
- rollback prouvé ;
- conventions repository documentées.

---
