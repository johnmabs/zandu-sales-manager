import type { FoundationApi } from "@zandu/api-client";

export function cancelStoreClosure(api: FoundationApi, storeId: string) {
  return api.cancelStoreClosure(storeId);
}
