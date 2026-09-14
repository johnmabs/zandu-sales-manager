import type { FoundationApi, ProductFilters } from "@zandu/api-client";

export function listProducts(
  api: FoundationApi,
  access: Parameters<FoundationApi["listProducts"]>[0],
  filters: ProductFilters = {},
) {
  return api.listProducts(access, filters);
}
