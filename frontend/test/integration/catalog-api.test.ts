import { http, HttpResponse } from "msw";
import { setupServer } from "msw/node";
import { afterAll, afterEach, beforeAll, describe, expect, it } from "vitest";

import { ApiClient, ApiContractError, FoundationApi } from "../../packages/api-client/src/index";

const product = {
  id: "product-1",
  organizationId: "org-1",
  productCode: "MED-001",
  name: "Paracétamol",
  description: null,
  status: "ACTIVE",
  type: "PHYSICAL",
  baseUnitId: "unit-1",
  inventoryTracked: true,
  taxCategoryId: null,
  categoryId: "category-1",
  createdAt: "2026-09-14T10:00:00Z",
  activatedAt: "2026-09-14T10:00:00Z",
  updatedAt: null,
  version: 1,
};
const access = {
  organizationId: "org-1",
  authorizationVersion: 1,
  permissions: ["PRODUCT_READ", "CATEGORY_READ"],
  accessibleStoreIds: [],
  scope: { type: "ORGANIZATION" } as const,
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

describe("Catalog API contracts", () => {
  it("sends filters to the server and preserves its order and tenant projection", async () => {
    server.use(
      http.get("https://api.zandu.test/api/products", ({ request }) => {
        expect(request.headers.get("authorization")).toBe("Bearer token");
        expect(Object.fromEntries(new URL(request.url).searchParams)).toEqual({
          status: "ACTIVE",
          type: "PHYSICAL",
          categoryId: "category-1",
          productCode: "MED-001",
          search: "A & B",
        });
        return HttpResponse.json([
          product,
          { ...product, id: "foreign", organizationId: "org-2" },
          { ...product, id: "second", productCode: "AAA" },
        ]);
      }),
    );
    const result = await api.listProducts(access, {
      status: "ACTIVE",
      type: "PHYSICAL",
      categoryId: "category-1",
      productCode: "MED-001",
      search: "A & B",
    });
    expect(result.map((p) => p.id)).toEqual(["product-1", "second"]);
  });
  it("accepts an empty collection and omits blank filters", async () => {
    server.use(
      http.get("https://api.zandu.test/api/products", ({ request }) => {
        expect(new URL(request.url).search).toBe("");
        return HttpResponse.json([]);
      }),
    );
    await expect(api.listProducts(access, { search: "" })).resolves.toEqual([]);
  });
  it("rejects malformed resources rather than displaying fabricated values", async () => {
    server.use(
      http.get("https://api.zandu.test/api/products", () =>
        HttpResponse.json([{ ...product, inventoryTracked: "yes" }]),
      ),
    );
    await expect(api.listProducts(access)).rejects.toBeInstanceOf(ApiContractError);
  });
  it("keeps server errors and their correlation ID", async () => {
    server.use(
      http.get("https://api.zandu.test/api/products", () =>
        HttpResponse.json({ code: "FORBIDDEN", correlationId: "catalog-ref" }, { status: 403 }),
      ),
    );
    await expect(api.listProducts(access)).rejects.toMatchObject({
      apiError: { status: 403, correlationId: "catalog-ref" },
    });
  });
  it("loads categories with a defensive tenant projection", async () => {
    const category = {
      id: "category-1",
      organizationId: "org-1",
      name: "Santé",
      parentCategoryId: null,
      status: "ACTIVE",
      createdAt: "2026-09-14T10:00:00Z",
      updatedAt: null,
      version: 1,
    };
    server.use(
      http.get("https://api.zandu.test/api/categories", () =>
        HttpResponse.json({
          member: [category, { ...category, id: "foreign", organizationId: "org-2" }],
        }),
      ),
    );
    await expect(api.listCategories(access)).resolves.toEqual([category]);
  });
});
