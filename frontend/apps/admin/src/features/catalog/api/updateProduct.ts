import type { FoundationApi, ProductUpdateInput } from "@zandu/api-client";

export function updateProduct(api: FoundationApi, productId: string, input: ProductUpdateInput) {
  return api.updateProduct(productId, input);
}
