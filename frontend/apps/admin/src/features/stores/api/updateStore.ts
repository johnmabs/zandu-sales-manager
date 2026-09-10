import type { FoundationApi, StoreUpdateInput } from "@zandu/api-client";

export function updateStore(api: FoundationApi, storeId: string, input: StoreUpdateInput) {
  return api.updateStore(storeId, input);
}
