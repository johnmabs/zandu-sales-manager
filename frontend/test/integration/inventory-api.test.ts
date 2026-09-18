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
const access = {
  organizationId: "org-1",
  authorizationVersion: 1,
  permissions: ["INVENTORY_READ", "STOCK_MOVEMENT_READ"],
  accessibleStoreIds: ["store-1"],
  scope: { type: "SELECTED_STORES" } as const,
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
