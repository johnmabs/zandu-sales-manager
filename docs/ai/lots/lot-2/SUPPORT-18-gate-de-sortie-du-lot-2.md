# 18. Gate de sortie du Lot 2

Le Lot 2 est `DONE` uniquement lorsque :

```text
[ ] module Catalog opérationnel
[ ] schema catalog disponible
[ ] architecture boundaries protégées

[ ] UnitOfMeasure opérationnel
[ ] précision et rounding explicites
[ ] aucun float utilisé

[ ] Category aggregate opérationnel
[ ] hiérarchie Category testée
[ ] cycles Category interdits

[ ] Product aggregate opérationnel
[ ] Product lifecycle testé
[ ] 1 SKU vendable = 1 Product
[ ] aucun ProductVariant MVP
[ ] ProductCode unique par tenant
[ ] ProductCode immuable après activation
[ ] baseUnit immutable après activation
[ ] SERVICE implique inventoryTracked=false
[ ] aucun delete métier de Product

[ ] ProductPackaging opérationnel
[ ] base packaging explicite
[ ] conversionFactor = 1 pour base packaging
[ ] conversions exactes testées
[ ] facteurs historiques protégés

[ ] Barcode opérationnel
[ ] barcode stocké comme string
[ ] zéros initiaux préservés
[ ] barcode unique par tenant
[ ] barcode resolver tenant-safe

[ ] PriceList opérationnel
[ ] ProductPrice opérationnel
[ ] prix cible ProductPackagingId
[ ] devise PriceList cohérente
[ ] Money exact
[ ] ProductPriceNotFound explicite
[ ] résolution de prix déterministe

[ ] permissions Catalog/Pricing ajoutées
[ ] rôles système mis à jour
[ ] AuthorizationService utilisé
[ ] OrganizationOperationalGuard actif

[ ] SecurityAuditEntry produit pour opérations sensibles
[ ] domain events disponibles
[ ] outbox transactionnelle
[ ] métier + audit + outbox atomiques

[ ] API Categories disponible
[ ] API Products disponible
[ ] API ProductPackaging disponible
[ ] API Barcode disponible
[ ] API PriceList disponible
[ ] API ProductPrice disponible
[ ] OpenAPI à jour
[ ] contrat d'erreurs stable

[ ] persistence PostgreSQL réelle
[ ] indexes et contraintes utiles
[ ] optimistic locking appliqué lorsque nécessaire
[ ] RLS activé sur données tenant-owned
[ ] cross-tenant retourne NOT_FOUND
[ ] deux connexions PostgreSQL confirment isolation locale du tenant

[ ] domain unit tests verts
[ ] integration tests verts
[ ] API contract tests verts
[ ] tenant isolation tests verts
[ ] RLS tests verts
[ ] architecture fitness tests verts
[ ] PHPStan vert
[ ] PHP-CS-Fixer vert
[ ] Composer audit vert
[ ] CI verte

[ ] démonstration consolidée du Lot 2 réussie

[ ] aucune logique Inventory prématurée
[ ] aucune logique Cash Management prématurée
[ ] aucune logique Sales prématurée
[ ] aucune logique Purchasing prématurée
[ ] aucune logique Reporting prématurée
[ ] aucune logique Offline prématurée

[ ] IMPLEMENTATION_STATUS.md mis à jour
[ ] documentation d’architecture mise à jour si décision DÉCIDÉ modifiée
[ ] ADR créé ou mis à jour pour toute nouvelle décision structurante
```

---
