# Spike G — Validation de l'infrastructure du Lot 0

**Date :** 2026-08-21  
**Statut :** VALIDÉ

## Périmètre validé

- l'image Docker de production installe uniquement les dépendances nécessaires,
  compile l'environnement `prod` et utilise un autoloader autoritatif ;
- les migrations Doctrine sont exécutées explicitement avant le démarrage de
  l'application ;
- `/health/live` confirme que le processus répond et `/health/ready` vérifie
  réellement PostgreSQL ;
- le traitement des signaux fournit le socle de graceful shutdown pour les
  futurs workers ;
- les traces HTTP OpenTelemetry sont exportables par OTLP, sans couplage à un
  fournisseur ;
- les cinq métriques worker/outbox requises sont exposées ;
- le scénario de staging local démarre l'image immuable contre PostgreSQL et
  valide le readiness check ;
- le scénario de sauvegarde/restauration restaure un dump dans une base isolée
  et contrôle les migrations présentes.

## Commandes reproductibles

```text
docker compose build backend
make staging-test
make backup-restore-test
```

Le 21 août 2026, ces trois commandes ont réussi. La base restaurée contenait les
deux versions de migration attendues.

## Limites et décisions

Le staging validé est un environnement local de type production. Le choix d'une
plateforme d'hébergement reste donc `PROPOSED` dans l'ADR-0012. De même,
OpenTelemetry et l'export OTLP sont opérationnels, mais aucun backend hébergé
n'est retenu ; Grafana Cloud reste `PROPOSED` dans l'ADR-0013.

Ces choix fournisseur ne bloquent pas le gate d'architecture : l'artefact, le
contrat de déploiement, l'observabilité et les procédures de restauration sont
testables sans dépendance à un compte externe.
