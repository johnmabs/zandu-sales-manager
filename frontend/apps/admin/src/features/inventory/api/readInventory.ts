import type {
  FoundationApi,
  StockAdjustInput,
  StockInitializeInput,
  StockMovementFilters,
  StockTransferCreateInput,
  StockTransferFilters,
  StockTransferLineCreateInput,
  StockTransferLineUpdateInput,
  StockTransferShipInput,
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

export function createStockTransfer(
  api: FoundationApi,
  input: StockTransferCreateInput,
  access: EffectiveAccess,
) {
  return api.createStockTransfer(input, access);
}

export function addStockTransferLine(
  api: FoundationApi,
  transferId: string,
  input: StockTransferLineCreateInput,
  access: EffectiveAccess,
) {
  return api.addStockTransferLine(transferId, input, access);
}

export function updateStockTransferLine(
  api: FoundationApi,
  transferId: string,
  lineId: string,
  input: StockTransferLineUpdateInput,
  access: EffectiveAccess,
) {
  return api.updateStockTransferLine(transferId, lineId, input, access);
}

export function removeStockTransferLine(api: FoundationApi, transferId: string, lineId: string) {
  return api.removeStockTransferLine(transferId, lineId);
}

export function shipStockTransfer(
  api: FoundationApi,
  transferId: string,
  input: StockTransferShipInput,
  access: EffectiveAccess,
  idempotencyKey: string,
) {
  return api.shipStockTransfer(transferId, input, access, idempotencyKey);
}
