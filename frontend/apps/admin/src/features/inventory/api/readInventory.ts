import type {
  FoundationApi,
  StockAdjustInput,
  StockInitializeInput,
  StockMovementFilters,
  StockTransferFilters,
} from "@zandu/api-client";

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

export function adjustStock(
  api: FoundationApi,
  storeId: string,
  productId: string,
  input: StockAdjustInput,
  access: EffectiveAccess,
) {
  return api.adjustStock(storeId, productId, input, access);
}

export function listStockMovements(
  api: FoundationApi,
  storeId: string,
  access: EffectiveAccess,
  productId?: string,
) {
  return api.listStockMovements(storeId, access, productId);
}

export function listStockMovementPage(
  api: FoundationApi,
  storeId: string,
  access: EffectiveAccess,
  filters: StockMovementFilters,
) {
  return api.listStockMovementPage(storeId, access, filters);
}

export function listInventoryValuations(
  api: FoundationApi,
  storeId: string,
  currency: string,
  access: EffectiveAccess,
) {
  return api.listInventoryValuations(storeId, currency, access);
}

export function getInventoryValuation(
  api: FoundationApi,
  storeId: string,
  productId: string,
  currency: string,
  access: EffectiveAccess,
) {
  return api.getInventoryValuation(storeId, productId, currency, access);
}

export function listInventoryValuationMovements(
  api: FoundationApi,
  storeId: string,
  productId: string,
  currency: string,
  access: EffectiveAccess,
  filters: Readonly<{ cursor?: string; limit?: number }>,
) {
  return api.listInventoryValuationMovements(storeId, productId, currency, access, filters);
}

export function listStockTransfers(
  api: FoundationApi,
  access: EffectiveAccess,
  filters: StockTransferFilters,
) {
  return api.listStockTransfers(access, filters);
}

export function getStockTransfer(api: FoundationApi, transferId: string, access: EffectiveAccess) {
  return api.getStockTransfer(transferId, access);
}
