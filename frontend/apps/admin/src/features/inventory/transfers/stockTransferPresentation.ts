import type { StockTransferStatus } from "@zandu/api-client";
import type { AccessibleStore } from "@zandu/store-context";

const statusLabels: Readonly<Record<StockTransferStatus, string>> = {
  CANCELLED: "Annulé",
  DRAFT: "Brouillon",
  RECEIVED: "Réceptionné",
  SHIPPED: "Expédié",
};

export function stockTransferStatusLabel(status: StockTransferStatus): string {
  return statusLabels[status];
}

export function stockTransferStoreLabel(
  storeId: string,
  stores: readonly AccessibleStore[],
): string {
  const store = stores.find((candidate) => candidate.id === storeId);
  return store === undefined ? storeId : `${store.name} (${store.id})`;
}
