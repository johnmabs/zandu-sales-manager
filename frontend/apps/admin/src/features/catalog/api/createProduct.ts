import type { FoundationApi, ProductCreateInput } from "@zandu/api-client";

export function createProduct(api: FoundationApi, input: ProductCreateInput) {
  return api.createProduct(input);
}
