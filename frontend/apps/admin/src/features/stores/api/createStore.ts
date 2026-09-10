import type { FoundationApi, StoreCreateInput } from "@zandu/api-client";

export function createStore(api: FoundationApi, input: StoreCreateInput) {
  return api.createStore(input);
}
