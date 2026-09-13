# F3 — Support permissions, erreurs et cache

Utiliser Foundation pour session, organisation active, permissions et erreurs corrélées.
PRODUCT_READ, PRICE_LIST_READ et PRODUCT_PRICE_READ sont explicitement cités ; rechercher les permissions de mutation dans le catalogue réel.
Le serveur reste l’autorité. Préserver les réponses NOT_FOUND cross-tenant (ADR-0017/0018).
Ne pas journaliser credentials ou tokens (ADR-0006/0013).

Contrat HTTP : 400, 401, 403, 404, 409, 422.
Mapper code produit dupliqué, barcode dupliqué, hiérarchie invalide, transition invalide, packaging invalide, absence/conflit de prix et refus d’accès ; préserver correlationId.
L’absence de prix est distincte de zéro et d’une erreur réseau/autorisation.

Invalider listes/détails et dépendances après mutation :
- Catalog : Product update/activation, Packaging creation/archival, Barcode mutation.
- Pricing : PriceList update/activation et ProductPrice update/activation.
- ProductPrice modifié → détail, collection, effective-price, résumé Pricing produit.
Les clés restent tenant-scoped et réutilisent les patterns Foundation.

Source : `docs/specs/planning/zandu-frontend-lot-f3-admin-catalog-pricing.md`, sections 35, 45–50.
