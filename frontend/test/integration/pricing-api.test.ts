import { http, HttpResponse } from "msw";
import { setupServer } from "msw/node";
import { afterAll, afterEach, beforeAll, describe, expect, it } from "vitest";

import { ApiClient, ApiContractError, FoundationApi } from "../../packages/api-client/src/index";

const server = setupServer();
beforeAll(() => server.listen({ onUnhandledRequest: "error" }));
afterEach(() => server.resetHandlers());
afterAll(() => server.close());
const api = new FoundationApi(
  new ApiClient({ config: { apiBaseUrl: "https://api.zandu.test/api/", appEnvironment: "test" } }),
);
const access = {
  organizationId: "org-1",
  authorizationVersion: 1,
  permissions: ["PRODUCT_READ", "CATEGORY_READ"],
  accessibleStoreIds: [],
  scope: { type: "ORGANIZATION" } as const,
};
describe("Pricing API foundation", () => {
  it("keeps money exact and uses the server result at the requested date", async () => {
    const price = {
      priceListId: "list-1",
      productPriceId: "price-1",
      amount: "9007199254740993.123456",
      currency: "XAF",
      sourceVersion: 2,
    };
    server.use(
      http.get(
        "https://api.zandu.test/api/products/p1/packagings/pack1/effective-price",
        ({ request }) => {
          expect(new URL(request.url).searchParams.get("at")).toBe("2026-09-14T10:00:00+01:00");
          return HttpResponse.json(price);
        },
      ),
    );
    await expect(
      api.getEffectiveProductPrice("p1", "pack1", "2026-09-14T10:00:00+01:00"),
    ).resolves.toEqual(price);
  });
  it("does not turn a missing price into zero", async () => {
    server.use(
      http.get("https://api.zandu.test/api/products/p1/packagings/pack1/effective-price", () =>
        HttpResponse.json({ code: "NOT_FOUND", correlationId: "price-ref" }, { status: 404 }),
      ),
    );
    await expect(api.getEffectiveProductPrice("p1", "pack1")).rejects.toMatchObject({
      apiError: { status: 404, correlationId: "price-ref" },
    });
  });
  it("rejects numeric money responses", async () => {
    server.use(
      http.get("https://api.zandu.test/api/products/p1/packagings/pack1/effective-price", () =>
        HttpResponse.json({
          priceListId: "list",
          productPriceId: "price",
          amount: 5,
          currency: "XAF",
          sourceVersion: 1,
        }),
      ),
    );
    await expect(api.getEffectiveProductPrice("p1", "pack1")).rejects.toBeInstanceOf(
      ApiContractError,
    );
  });
  it("projects price lists and product prices to the active tenant", async () => {
    const list = {
      id: "list",
      organizationId: "org-1",
      code: "RETAIL",
      name: "Détail",
      currency: "XAF",
      status: "ACTIVE",
      scope: "ORGANIZATION",
      validFrom: null,
      validTo: null,
      priority: 1,
      createdAt: "2026-09-14",
      version: 1,
    };
    const price = {
      id: "price",
      organizationId: "org-1",
      priceListId: "list",
      productId: "p1",
      packagingId: "pack1",
      amount: "0.00",
      currency: "XAF",
      status: "ACTIVE",
      validFrom: null,
      validTo: null,
      createdAt: "2026-09-14",
      version: 1,
    };
    server.use(
      http.get("https://api.zandu.test/api/price-lists", () =>
        HttpResponse.json([list, { ...list, organizationId: "foreign" }]),
      ),
      http.get("https://api.zandu.test/api/product-prices", () =>
        HttpResponse.json([price, { ...price, organizationId: "foreign" }]),
      ),
    );
    await expect(api.listPriceLists(access)).resolves.toEqual([list]);
    await expect(api.listProductPrices(access)).resolves.toEqual([price]);
  });
  it("rejects unknown Pricing lifecycle and scope projections", async () => {
    const list = {
      id: "list",
      organizationId: "org-1",
      code: "RETAIL",
      name: "Détail",
      currency: "XAF",
      status: "DELETED",
      scope: "ORGANIZATION",
      validFrom: null,
      validTo: null,
      priority: 1,
      createdAt: "2026-09-14",
      version: 1,
    };
    server.use(http.get("https://api.zandu.test/api/price-lists", () => HttpResponse.json([list])));
    await expect(api.listPriceLists(access)).rejects.toBeInstanceOf(ApiContractError);
    server.use(
      http.get("https://api.zandu.test/api/price-lists", () =>
        HttpResponse.json([{ ...list, status: "ACTIVE", scope: "STORE" }]),
      ),
    );
    await expect(api.listPriceLists(access)).rejects.toBeInstanceOf(ApiContractError);
  });
  it("rejects numeric collection amounts and unknown ProductPrice statuses", async () => {
    const price = {
      id: "price",
      organizationId: "org-1",
      priceListId: "list",
      productId: "p1",
      packagingId: "pack1",
      amount: 12.5,
      currency: "XAF",
      status: "ACTIVE",
      validFrom: null,
      validTo: null,
      createdAt: "2026-09-14",
      version: 1,
    };
    server.use(
      http.get("https://api.zandu.test/api/product-prices", () => HttpResponse.json([price])),
    );
    await expect(api.listProductPrices(access)).rejects.toBeInstanceOf(ApiContractError);
    server.use(
      http.get("https://api.zandu.test/api/product-prices", () =>
        HttpResponse.json([{ ...price, amount: "12.50", status: "DRAFT" }]),
      ),
    );
    await expect(api.listProductPrices(access)).rejects.toBeInstanceOf(ApiContractError);
  });
});
