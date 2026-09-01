# 16. Gate de sortie du Lot 1

Le Lot 1 est `DONE` uniquement lorsque :

```text
[x] Organization aggregate opérationnel
[x] Organization lifecycle testé
[x] Store aggregate opérationnel
[x] Store lifecycle testé
[x] multi-store support validé
[x] OrganizationInvitation opérationnelle
[x] invitation token sécurisé
[x] invitation expiry/cancel/accept testés
[x] OrganizationMembership opérationnel
[x] membership lifecycle testé
[x] authorizationVersion effectif
[x] permission catalog Lot 1 disponible
[x] rôles système MVP disponibles
[x] RoleAssignment disponible
[x] AccessScope ORGANIZATION disponible
[x] AccessScope SELECTED_STORES disponible
[x] invariant du dernier owner protégé
[x] compte utilisateur persistant disponible
[x] inscription du premier owner disponible
[x] inscription depuis une invitation disponible
[x] AuthorizationService utilisé par les handlers
[x] OrganizationOperationalGuard actif
[x] StoreOperationalGuard actif
[x] tenant-scoped repositories
[x] PostgreSQL RLS actif sur toutes les tables tenant-owned
[x] rôle applicatif sans BYPASSRLS
[x] contexte tenant transactionnel sans fuite entre connexions
[x] aucune fuite d’existence cross-tenant
[x] SecurityAuditEntry opérationnel
[x] opérations sensibles auditées
[x] audit + métier + outbox atomiques
[x] API administration disponible
[x] OpenAPI à jour
[x] tests PostgreSQL réels au vert
[x] architecture fitness tests au vert
[x] CI au vert
[x] démonstration métier du Lot 1 réussie
[x] documentation d’architecture mise à jour si une décision DÉCIDÉ a changé
[x] ADR créé ou mis à jour pour toute nouvelle décision structurante
```

Gate validée le 25 août 2026 sur PostgreSQL réel : suite complète de 282 tests
et 1138 assertions, démonstration consolidée de 13 tests et 283 assertions,
smoke test de l'image de staging et cycle sauvegarde/restauration réussis.

---
