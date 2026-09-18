import type { StockMovementSource, StockMovementType } from "@zandu/api-client";

const incomingTypes = new Set<StockMovementType>([
  "ADJUSTMENT_IN",
  "GOODS_RECEIPT_CORRECTION_IN",
  "INITIAL_STOCK",
  "PURCHASE_RECEIPT",
  "SALE_RETURN",
  "STOCK_COUNT_CORRECTION_IN",
  "TRANSFER_IN",
]);

const typeLabels: Record<StockMovementType, string> = {
  ADJUSTMENT_IN: "Ajustement entrant",
  ADJUSTMENT_OUT: "Ajustement sortant",
  GOODS_RECEIPT_CORRECTION_IN: "Correction de réception entrante",
  GOODS_RECEIPT_CORRECTION_OUT: "Correction de réception sortante",
  INITIAL_STOCK: "Stock initial",
  PURCHASE_RECEIPT: "Réception d’achat",
  PURCHASE_RETURN: "Retour fournisseur",
  SALE: "Vente",
  SALE_RETURN: "Retour de vente",
  STOCK_COUNT_CORRECTION_IN: "Correction d’inventaire entrante",
  STOCK_COUNT_CORRECTION_OUT: "Correction d’inventaire sortante",
  TRANSFER_IN: "Transfert entrant",
  TRANSFER_OUT: "Transfert sortant",
};

const sourceLabels: Record<StockMovementSource, string> = {
  GOODS_RECEIPT: "Réception de marchandises",
  GOODS_RECEIPT_CORRECTION: "Correction de réception",
  INITIALIZATION: "Initialisation",
  MANUAL_ADJUSTMENT: "Ajustement manuel",
  PURCHASE_RETURN: "Retour fournisseur",
  RETURN: "Retour de vente",
  SALE: "Vente",
  STOCK_COUNT: "Inventaire physique",
  TRANSFER: "Transfert de stock",
};

export function stockMovementDirection(type: StockMovementType): "Entrée" | "Sortie" {
  return incomingTypes.has(type) ? "Entrée" : "Sortie";
}

export function stockMovementTypeLabel(type: StockMovementType): string {
  return typeLabels[type];
}

export function stockMovementSourceLabel(source: StockMovementSource): string {
  return sourceLabels[source];
}
