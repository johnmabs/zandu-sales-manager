import type { FoundationApi } from "@zandu/api-client";

type StoreAccess = Parameters<FoundationApi["getAccessibleStore"]>[1];

/** The client keeps an unexpected out-of-scope response indistinguishable from absence. */
export function getStore(api: FoundationApi, storeId: string, access: StoreAccess) {
  return api.getAccessibleStore(storeId, access);
}
