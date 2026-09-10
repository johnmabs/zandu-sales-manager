import type { FoundationApi } from "@zandu/api-client";

export function suspendStore(api: FoundationApi, storeId: string) {
  return api.suspendStore(storeId);
}
