# 5. Epic 0.2 — Architecture fitness tests

## Objectif

Transformer les règles DDD en contraintes automatiquement vérifiables.

## Étape 0.2.1 — Ajouter l’outil de test d’architecture

Choisir et intégrer un outil adapté à PHP permettant de tester les dépendances de namespaces.

### Commit proposé

```text
build(architecture): add dependency rule tooling
```

---

## Étape 0.2.2 — Protéger le Domain

Règles minimales :

```text
Domain
→ SharedKernel
```

Interdictions :

```text
Domain
✗ Symfony
✗ Doctrine
✗ API Platform
✗ Infrastructure
✗ Presentation
```

### Commit proposé

```text
test(architecture): enforce Domain dependency rules
```

---

## Étape 0.2.3 — Protéger les bounded contexts

Interdire :

```text
Sales
→ Inventory\Domain
Sales
→ Inventory\Infrastructure
```

Autoriser uniquement les contrats publics prévus :

```text
Sales
→ Inventory\Application\Contract
```

### Commit proposé

```text
test(architecture): enforce bounded context boundaries
```

---

## Étape 0.2.4 — Protéger les couches

Formaliser les dépendances autorisées entre :

```text
Domain
Application
Infrastructure
Presentation
```

### Commit proposé

```text
test(architecture): enforce application layer boundaries
```

---

## Definition of Done — Epic 0.2

- une violation volontaire fait échouer les tests ;
- les dépendances cross-context non autorisées sont détectées ;
- les règles sont documentées ;
- les tests tournent dans la CI.

---
