# 8. Epic 0.5 — API foundation

## Objectif

Rendre le flux HTTP → Application exécutable sans exposer le modèle métier.

## Étape 0.5.1 — Installer API Platform

### Commit proposé

```text
build(api): add API Platform and OpenAPI
```

---

## Étape 0.5.2 — Définir la convention `StateProcessor`

Prouver :

```text
HTTP
→ StateProcessor
→ Application Command
→ Result
```

### Commit proposé

```text
feat(api): add application command processor pattern
```

---

## Étape 0.5.3 — Définir la convention `StateProvider`

Prouver :

```text
HTTP
→ StateProvider
→ Application Query
→ Read Model
```

### Commit proposé

```text
feat(api): add application query provider pattern
```

---

## Étape 0.5.4 — Normaliser les erreurs

Format minimal :

```json
{
  "code": "STABLE_ERROR_CODE",
  "message": "Human-readable message",
  "correlationId": "..."
}
```

Le client ne doit jamais prendre une décision métier à partir du texte de `message`.

### Commit proposé

```text
feat(api): add stable application error responses
```

---

## Étape 0.5.5 — Correlation ID HTTP

Créer ou propager un `CorrelationId` par requête.

### Commit proposé

```text
feat(api): add correlation id propagation
```

---

## Étape 0.5.6 — Idempotency-Key foundation

Mettre en place le socle HTTP nécessaire aux futures commands critiques.

### Commit proposé

```text
feat(api): add idempotency key request handling
```

---

## Definition of Done — Epic 0.5

- OpenAPI généré ;
- processor et provider testés ;
- erreurs stables ;
- correlation propagée ;
- aucune entity Doctrine directement utilisée comme ressource métier critique.

---
