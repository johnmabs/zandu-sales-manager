# 11. Epic 0.8 — Operations & observability

## Étape 0.8.1 — Structured logging

### Commit proposé

```text
feat(observability): add structured application logging
```

---

## Étape 0.8.2 — OpenTelemetry

Instrumenter progressivement HTTP, application workflows, persistence, outbox et workers.

### Commit proposé

```text
feat(observability): add OpenTelemetry instrumentation
```

---

## Étape 0.8.3 — Health endpoints

```text
/health/live
/health/ready
```

### Commit proposé

```text
feat(operations): add liveness and readiness endpoints
```

---

## Étape 0.8.4 — Graceful shutdown

API et workers doivent terminer proprement leurs traitements selon les contraintes définies.

### Commit proposé

```text
feat(operations): add graceful process shutdown
```

---

## Étape 0.8.5 — Métriques workers et outbox

Au minimum :

```text
outbox_pending_count
outbox_oldest_pending_age
outbox_publish_failures
worker_retry_count
dead_letter_count
```

Ces métriques sont calculées depuis l'état persistant de l'outbox PostgreSQL,
afin de rester cohérentes entre plusieurs workers et après redémarrage.

### Commit proposé

```text
feat(observability): add worker and outbox metrics
```

---

## Spike G — Infrastructure

Tester :

```text
Docker image
staging deployment
PostgreSQL
controlled migration
worker
health checks
OpenTelemetry export
backup
restore
```

### Commits proposés

```text
ci(deploy): add staging deployment pipeline
chore(deploy): add controlled migration step
test(operations): verify deployment health checks
test(backup): verify PostgreSQL restore procedure
docs(spike-g): document infrastructure validation
```

Si Render est validé :

```text
docs(adr): record initial deployment platform
```

Si Grafana Cloud est validé :

```text
docs(adr): record initial observability backend
```

---
