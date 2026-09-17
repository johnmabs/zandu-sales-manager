# F4 — Support domaine et contrat API

- Stock : id, organizationId, storeId, productId, quantityOnHand string,
  initialized, version.
- StockMovement : id, storeId, productId, stockId, type, quantity,
  previousQuantity, resultingQuantity, source, reason?, occurredAt.
- Stock et valuation sont des autorités distinctes ; aucun `Product.costPrice`.
- Initialisation unique ; ajustement non nul et justifié ; aucun stock négatif.
- Transfert : DRAFT → SHIPPED → RECEIVED ou DRAFT → CANCELLED.
- Comptage : zéro explicite distinct de non saisi ; la finalisation reste serveur.
- Vérifier le schéma OpenAPI réel avant chaque formulaire ou mutation.

Relevant ADRs : ADR-0005, ADR-0008, ADR-0015, ADR-0021, ADR-0025.

Source : `docs/specs/planning/zandu-frontend-lot-f4-admin-inventory.md`, sections 6–11.

