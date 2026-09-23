import { http, HttpResponse } from "msw";
import { setupServer } from "msw/node";
import { afterAll, afterEach, beforeAll, describe, expect, it } from "vitest";

import { ApiClient, ApiContractError, FoundationApi } from "../../packages/api-client/src/index";

const stock = {
  id: "stock-1",
  organizationId: "org-1",
  storeId: "store-1",
  productId: "product-1",
  quantityOnHand: "9007199254740993.125",
  initialized: true,
  version: 2,
};
const movement = {
  id: "movement-1",
  storeId: "store-1",
  productId: "product-1",
  stockId: "stock-1",
  type: "ADJUSTMENT_IN",
  quantity: "0.125",
  previousQuantity: "9007199254740993.000",
  resultingQuantity: "9007199254740993.125",
  source: "MANUAL_ADJUSTMENT",
  reason: "Correction de comptage",
  occurredAt: "2026-09-18T08:00:00+01:00",
};
const valuation = {
  id: "valuation-1",
  organizationId: "org-1",
  storeId: "store-1",
  productId: "product-1",
  stockId: "stock-1",
  quantityOnHand: "9007199254740993.125",
  totalValue: "36028797018963972500.125000",
  currency: "XAF",
  averageUnitCost: "4000.125000000000",
  version: 2,
};
const valuationMovement = {
  id: "valuation-movement-1",
  stockValuationId: "valuation-1",
  organizationId: "org-1",
  storeId: "store-1",
  productId: "product-1",
  stockId: "stock-1",
  stockMovementId: "movement-1",
  type: "INITIAL_STOCK",
  quantity: "9007199254740993.125",
  unitCost: "4000.125000000000",
  value: "36028797018963972500.125000",
  previousTotalValue: "0.000000",
  resultingTotalValue: "36028797018963972500.125000",
  previousAverageCost: "0.000000000000",
  resultingAverageCost: "4000.125000000000",
  currency: "XAF",
  sourceType: "INITIALIZATION",
  sourceReferenceId: null,
  occurredAt: "2026-09-18T08:00:00+01:00",
  correlationId: "valuation-correlation",
};
const transfer = {
  id: "transfer-1",
  sourceStoreId: "store-1",
  destinationStoreId: "store-2",
  status: "RECEIVED",
  lines: [
    {
      id: "transfer-line-1",
      productId: "product-1",
      requestedQuantity: "9007199254740993.125000",
      shippedQuantity: "3.000000",
      receivedQuantity: "2.000000",
      transitDiscrepancy: "1.000000000000",
    },
  ],
  hasTransitDiscrepancy: true,
  createdAt: "2026-09-20T08:00:00+01:00",
  shippedAt: "2026-09-20T09:00:00+01:00",
  receivedAt: "2026-09-20T10:00:00+01:00",
  cancellationReason: null,
  cancelledAt: null,
  version: 4,
};
const access = {
  organizationId: "org-1",
  authorizationVersion: 1,
  permissions: ["INVENTORY_READ", "STOCK_MOVEMENT_READ", "STOCK_TRANSFER_READ"],
  accessibleStoreIds: ["store-1"],
  scope: { storeIds: ["store-1"], type: "SELECTED_STORES" } as const,
};
const transferWriteAccess = {
  ...access,
  accessibleStoreIds: ["store-1", "store-2"],
  permissions: [...access.permissions, "STOCK_TRANSFER_CREATE", "STOCK_TRANSFER_UPDATE"],
  scope: { storeIds: ["store-1", "store-2"], type: "SELECTED_STORES" } as const,
};
const server = setupServer();
beforeAll(() => server.listen({ onUnhandledRequest: "error" }));
afterEach(() => server.resetHandlers());
afterAll(() => server.close());
const api = new FoundationApi(
  new ApiClient({
    config: { apiBaseUrl: "https://api.zandu.test/api/", appEnvironment: "test" },
    session: {
      getAccessToken: () => "token",
      refreshAndRetry: async (retry) => retry("new-token"),
    },
  }),
);

describe("Inventory API contracts", () => {
  it("preserves exact stock quantities and the server order", async () => {
    server.use(
      http.get("https://api.zandu.test/api/stores/store-1/stocks", ({ request }) => {
        expect(request.headers.get("authorization")).toBe("Bearer token");
        return HttpResponse.json([stock, { ...stock, id: "stock-2", productId: "product-2" }]);
      }),
    );
    await expect(api.listStocks("store-1", access)).resolves.toEqual([
      stock,
      { ...stock, id: "stock-2", productId: "product-2" },
    ]);
  });

  it("projects stock collections and details to the active tenant and store", async () => {
    server.use(
      http.get("https://api.zandu.test/api/stores/store-1/stocks", () =>
        HttpResponse.json([
          stock,
          { ...stock, id: "foreign-tenant", organizationId: "org-2" },
          { ...stock, id: "foreign-store", storeId: "store-2" },
        ]),
      ),
      http.get("https://api.zandu.test/api/stores/store-1/stocks/product-1", () =>
        HttpResponse.json(stock),
      ),
      http.get("https://api.zandu.test/api/stores/store-1/stocks/product-2", () =>
        HttpResponse.json({ ...stock, productId: "product-1" }),
      ),
      http.get("https://api.zandu.test/api/stores/store-1/stocks/product-3", () =>
        HttpResponse.json({ code: "NOT_FOUND" }, { status: 404 }),
      ),
    );
    await expect(api.listStocks("store-1", access)).resolves.toEqual([stock]);
    await expect(api.getStock("store-1", "product-1", access)).resolves.toEqual(stock);
    await expect(api.getStock("store-1", "product-2", access)).resolves.toBeUndefined();
    await expect(api.getStock("store-1", "product-3", access)).resolves.toBeUndefined();
  });

  it("initializes stock with exact decimal strings and validates the returned scope", async () => {
    server.use(
      http.post(
        "https://api.zandu.test/api/stores/store-1/stocks/product-1/initialize",
        async ({ request }) => {
          await expect(request.json()).resolves.toEqual({
            quantity: "001.250",
            unitCost: "000800.125000",
          });
          return HttpResponse.json({ ...stock, quantityOnHand: "001.250" }, { status: 201 });
        },
      ),
    );
    await expect(
      api.initializeStock(
        "store-1",
        "product-1",
        { quantity: "001.250", unitCost: "000800.125000" },
        access,
      ),
    ).resolves.toEqual({ ...stock, quantityOnHand: "001.250" });

    server.use(
      http.post("https://api.zandu.test/api/stores/store-1/stocks/product-1/initialize", () =>
        HttpResponse.json({ ...stock, organizationId: "org-2" }, { status: 201 }),
      ),
    );
    await expect(
      api.initializeStock("store-1", "product-1", { quantity: "1", unitCost: "800" }, access),
    ).rejects.toBeInstanceOf(ApiContractError);
  });

  it("adjusts stock with exact incoming and outgoing payloads", async () => {
    let expectedBody: unknown = {
      delta: "001.250",
      reason: "Réassort manuel",
      unitCost: "000800.125000",
    };
    server.use(
      http.post(
        "https://api.zandu.test/api/stores/store-1/stocks/product-1/adjust",
        async ({ request }) => {
          await expect(request.json()).resolves.toEqual(expectedBody);
          return HttpResponse.json({ ...stock, quantityOnHand: "9007199254740994.375" });
        },
      ),
    );
    await expect(
      api.adjustStock(
        "store-1",
        "product-1",
        {
          delta: "001.250",
          reason: "Réassort manuel",
          unitCost: "000800.125000",
        },
        access,
      ),
    ).resolves.toMatchObject({ quantityOnHand: "9007199254740994.375" });

    expectedBody = { delta: "-000.125", reason: "Casse constatée" };
    await expect(
      api.adjustStock(
        "store-1",
        "product-1",
        { delta: "-000.125", reason: "Casse constatée" },
        access,
      ),
    ).resolves.toMatchObject({ quantityOnHand: "9007199254740994.375" });
  });

  it("reads store and product movement ledgers without changing exact decimals", async () => {
    server.use(
      http.get("https://api.zandu.test/api/stores/store-1/stock-movements", () =>
        HttpResponse.json({
          member: [movement, { ...movement, id: "foreign-store", storeId: "store-2" }],
        }),
      ),
      http.get("https://api.zandu.test/api/stores/store-1/stocks/product-1/movements", () =>
        HttpResponse.json([movement, { ...movement, id: "foreign-product", productId: "other" }]),
      ),
    );
    await expect(api.listStockMovements("store-1", access)).resolves.toEqual([movement]);
    await expect(api.listStockMovements("store-1", access, "product-1")).resolves.toEqual([
      movement,
    ]);
  });

  it("reads an opaque cursor page without inventing client-side pagination", async () => {
    server.use(
      http.get("https://api.zandu.test/api/stores/store-1/stock-movements", ({ request }) => {
        const url = new URL(request.url);
        expect(url.searchParams.get("limit")).toBe("25");
        expect(url.searchParams.get("cursor")).toBe("opaque+/cursor");
        return HttpResponse.json([movement, { ...movement, id: "foreign", storeId: "store-2" }], {
          headers: { "X-Next-Cursor": "next-page" },
        });
      }),
    );
    await expect(
      api.listStockMovementPage("store-1", access, {
        cursor: "opaque+/cursor",
        limit: 25,
      }),
    ).resolves.toEqual({ items: [movement], nextCursor: "next-page" });
  });

  it("reads scoped valuation summaries, detail and cursor ledger with exact amounts", async () => {
    server.use(
      http.get("https://api.zandu.test/api/stores/store-1/inventory-valuations", () =>
        HttpResponse.json([valuation, { ...valuation, id: "foreign", organizationId: "org-2" }]),
      ),
      http.get("https://api.zandu.test/api/stores/store-1/inventory-valuations/product-1", () =>
        HttpResponse.json(valuation),
      ),
      http.get(
        "https://api.zandu.test/api/stores/store-1/inventory-valuations/product-1/movements",
        ({ request }) => {
          const url = new URL(request.url);
          expect(url.searchParams.get("limit")).toBe("25");
          expect(url.searchParams.get("cursor")).toBe("valuation/cursor+");
          return HttpResponse.json(
            [valuationMovement, { ...valuationMovement, id: "foreign", productId: "product-2" }],
            { headers: { "X-Next-Cursor": "next-valuation-page" } },
          );
        },
      ),
    );
    await expect(api.listInventoryValuations("store-1", "XAF", access)).resolves.toEqual([
      valuation,
    ]);
    await expect(api.getInventoryValuation("store-1", "product-1", "XAF", access)).resolves.toEqual(
      valuation,
    );
    await expect(
      api.listInventoryValuationMovements("store-1", "product-1", "XAF", access, {
        cursor: "valuation/cursor+",
        limit: 25,
      }),
    ).resolves.toEqual({ items: [valuationMovement], nextCursor: "next-valuation-page" });

    server.use(
      http.get("https://api.zandu.test/api/stores/store-1/inventory-valuations", () =>
        HttpResponse.json([{ ...valuation, currency: "EUR" }]),
      ),
    );
    await expect(api.listInventoryValuations("store-1", "XAF", access)).rejects.toBeInstanceOf(
      ApiContractError,
    );
  });

  it("reads scoped transfer pages and details with exact line quantities", async () => {
    server.use(
      http.get("https://api.zandu.test/api/stock-transfers", ({ request }) => {
        const url = new URL(request.url);
        expect(url.searchParams.get("limit")).toBe("25");
        expect(url.searchParams.get("cursor")).toBe("transfer/cursor+");
        return HttpResponse.json(
          [
            transfer,
            {
              ...transfer,
              id: "foreign-transfer",
              sourceStoreId: "store-3",
              destinationStoreId: "store-4",
            },
          ],
          { headers: { "X-Next-Cursor": "next-transfer-page" } },
        );
      }),
      http.get("https://api.zandu.test/api/stock-transfers/transfer-1", () =>
        HttpResponse.json(transfer),
      ),
      http.get("https://api.zandu.test/api/stock-transfers/foreign-transfer", () =>
        HttpResponse.json({
          ...transfer,
          id: "foreign-transfer",
          sourceStoreId: "store-3",
          destinationStoreId: "store-4",
        }),
      ),
      http.get("https://api.zandu.test/api/stock-transfers/missing-transfer", () =>
        HttpResponse.json({ code: "NOT_FOUND" }, { status: 404 }),
      ),
      http.get("https://api.zandu.test/api/stock-transfers/mismatched-transfer", () =>
        HttpResponse.json(transfer),
      ),
    );

    await expect(
      api.listStockTransfers(access, { cursor: "transfer/cursor+", limit: 25 }),
    ).resolves.toEqual({ items: [transfer], nextCursor: "next-transfer-page" });
    await expect(api.getStockTransfer("transfer-1", access)).resolves.toEqual(transfer);
    await expect(api.getStockTransfer("foreign-transfer", access)).resolves.toBeUndefined();
    await expect(api.getStockTransfer("missing-transfer", access)).resolves.toBeUndefined();
    await expect(api.getStockTransfer("mismatched-transfer", access)).resolves.toBeUndefined();
  });

  it("creates a draft and mutates lines with exact quantities and expectedVersion", async () => {
    const draft = {
      ...transfer,
      hasTransitDiscrepancy: false,
      lines: [],
      receivedAt: null,
      shippedAt: null,
      status: "DRAFT",
      version: 1,
    };
    server.use(
      http.post("https://api.zandu.test/api/stock-transfers", async ({ request }) => {
        await expect(request.json()).resolves.toEqual({
          destinationStoreId: "store-2",
          sourceStoreId: "store-1",
        });
        return HttpResponse.json(draft, { status: 201 });
      }),
      http.post(
        "https://api.zandu.test/api/stock-transfers/transfer-1/lines",
        async ({ request }) => {
          await expect(request.json()).resolves.toEqual({
            productId: "product-1",
            requestedQuantity: "001.250000",
          });
          return HttpResponse.json(
            {
              ...draft,
              lines: [
                {
                  ...transfer.lines[0],
                  receivedQuantity: null,
                  requestedQuantity: "001.250000",
                  shippedQuantity: null,
                  transitDiscrepancy: null,
                },
              ],
              version: 2,
            },
            { status: 201 },
          );
        },
      ),
      http.patch(
        "https://api.zandu.test/api/stock-transfers/transfer-1/lines/transfer-line-1",
        async ({ request }) => {
          await expect(request.json()).resolves.toEqual({
            expectedVersion: 2,
            requestedQuantity: "000.125000",
          });
          return HttpResponse.json({
            ...draft,
            lines: [
              {
                ...transfer.lines[0],
                receivedQuantity: null,
                requestedQuantity: "000.125000",
                shippedQuantity: null,
                transitDiscrepancy: null,
              },
            ],
            version: 3,
          });
        },
      ),
      http.delete(
        "https://api.zandu.test/api/stock-transfers/transfer-1/lines/transfer-line-1",
        () => new HttpResponse(null, { status: 204 }),
      ),
    );

    await expect(
      api.createStockTransfer(
        { destinationStoreId: "store-2", sourceStoreId: "store-1" },
        transferWriteAccess,
      ),
    ).resolves.toEqual(draft);
    await expect(
      api.addStockTransferLine(
        "transfer-1",
        { productId: "product-1", requestedQuantity: "001.250000" },
        transferWriteAccess,
      ),
    ).resolves.toMatchObject({ version: 2 });
    await expect(
      api.updateStockTransferLine(
        "transfer-1",
        "transfer-line-1",
        { expectedVersion: 2, requestedQuantity: "000.125000" },
        transferWriteAccess,
      ),
    ).resolves.toMatchObject({ version: 3 });
    await expect(
      api.removeStockTransferLine("transfer-1", "transfer-line-1"),
    ).resolves.toBeUndefined();
  });

  it("rejects transfer mutation responses outside the writable scope", async () => {
    server.use(
      http.post("https://api.zandu.test/api/stock-transfers", () =>
        HttpResponse.json({ ...transfer, destinationStoreId: "store-3" }, { status: 201 }),
      ),
    );
    await expect(
      api.createStockTransfer(
        { destinationStoreId: "store-2", sourceStoreId: "store-1" },
        transferWriteAccess,
      ),
    ).rejects.toBeInstanceOf(ApiContractError);
  });

  it("rejects malformed transfer quantities and unknown statuses", async () => {
    server.use(
      http.get("https://api.zandu.test/api/stock-transfers", () =>
        HttpResponse.json([
          {
            ...transfer,
            lines: [{ ...transfer.lines[0], requestedQuantity: 12.5 }],
          },
        ]),
      ),
    );
    await expect(api.listStockTransfers(access)).rejects.toBeInstanceOf(ApiContractError);

    server.use(
      http.get("https://api.zandu.test/api/stock-transfers", () =>
        HttpResponse.json([{ ...transfer, status: "IN_TRANSIT" }]),
      ),
    );
    await expect(api.listStockTransfers(access)).rejects.toBeInstanceOf(ApiContractError);
  });

  it("rejects numeric quantities and unknown movement classifications", async () => {
    server.use(
      http.get("https://api.zandu.test/api/stores/store-1/stocks", () =>
        HttpResponse.json([{ ...stock, quantityOnHand: 12.5 }]),
      ),
    );
    await expect(api.listStocks("store-1", access)).rejects.toBeInstanceOf(ApiContractError);

    server.use(
      http.get("https://api.zandu.test/api/stores/store-1/stock-movements", () =>
        HttpResponse.json([{ ...movement, type: "UNKNOWN" }]),
      ),
    );
    await expect(api.listStockMovements("store-1", access)).rejects.toBeInstanceOf(
      ApiContractError,
    );

    server.use(
      http.get("https://api.zandu.test/api/stores/store-1/stock-movements", () =>
        HttpResponse.json([{ ...movement, source: "UNKNOWN" }]),
      ),
    );
    await expect(api.listStockMovements("store-1", access)).rejects.toBeInstanceOf(
      ApiContractError,
    );
  });

  it("preserves correlated server errors", async () => {
    server.use(
      http.get("https://api.zandu.test/api/stores/store-1/stocks", () =>
        HttpResponse.json({ code: "FORBIDDEN", correlationId: "inventory-ref" }, { status: 403 }),
      ),
    );
    await expect(api.listStocks("store-1", access)).rejects.toMatchObject({
      apiError: { correlationId: "inventory-ref", status: 403 },
    });
  });
});
