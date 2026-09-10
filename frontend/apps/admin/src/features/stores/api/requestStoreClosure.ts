import type { FoundationApi, StoreClosureRequestInput } from "@zandu/api-client";

export function requestStoreClosure(
  api: FoundationApi,
  storeId: string,
  input: StoreClosureRequestInput,
) {
  return api.requestStoreClosure(storeId, input);
}
