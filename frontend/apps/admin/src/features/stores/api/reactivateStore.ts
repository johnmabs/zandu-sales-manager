import type { FoundationApi } from "@zandu/api-client";

export function reactivateStore(api: FoundationApi, storeId: string) {
  return api.reactivateStore(storeId);
}
