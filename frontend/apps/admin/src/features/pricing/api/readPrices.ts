import type { FoundationApi } from "@zandu/api-client";

export function listPriceLists(
  api: FoundationApi,
  access: Parameters<FoundationApi["listPriceLists"]>[0],
) {
  return api.listPriceLists(access);
}
export function listProductPrices(
  api: FoundationApi,
  access: Parameters<FoundationApi["listProductPrices"]>[0],
) {
  return api.listProductPrices(access);
}
export function getEffectiveProductPrice(
  api: FoundationApi,
  productId: string,
  packagingId: string,
  at?: string,
) {
  return api.getEffectiveProductPrice(productId, packagingId, at);
}
