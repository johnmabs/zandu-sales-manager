import type { FoundationApi, StockInitializeInput } from "@zandu/api-client";

type EffectiveAccess = Parameters<FoundationApi["listStocks"]>[1];

export function listStocks(api: FoundationApi, storeId: string, access: EffectiveAccess) {
  return api.listStocks(storeId, access);
}

export function getStock(
  api: FoundationApi,
  storeId: string,
  productId: string,
  access: EffectiveAccess,
) {
  return api.getStock(storeId, productId, access);
}

export function initializeStock(
  api: FoundationApi,
  storeId: string,
  productId: string,
  input: StockInitializeInput,
  access: EffectiveAccess,
) {
  return api.initializeStock(storeId, productId, input, access);
}

export function listStockMovements(
  api: FoundationApi,
  storeId: string,
  access: EffectiveAccess,
  productId?: string,
) {
  return api.listStockMovements(storeId, access, productId);
}
