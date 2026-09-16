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
const packaging = {
  id: "packaging-1",
  organizationId: "org-1",
  productId: "product-1",
  base: false,
  code: "BOX-10",
  name: "Boîte de 10",
  unitId: "unit-1",
  conversionFactor: "10",
  precision: 0,
  minimumQuantity: "1",
  quantityIncrement: "1",
  allowedForSale: true,
  allowedForPurchase: false,
  status: "ACTIVE",
  createdAt: "2026-09-14T10:00:00Z",
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
  it("rejects unknown Product type and lifecycle values", async () => {
    server.use(
      http.get("https://api.zandu.test/api/products", () =>
        HttpResponse.json([{ ...product, status: "DELETED" }]),
      ),
    );
    await expect(api.listProducts(access)).rejects.toBeInstanceOf(ApiContractError);
    server.use(
      http.get("https://api.zandu.test/api/products", () =>
        HttpResponse.json([{ ...product, type: "DIGITAL" }]),
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
  it("rejects unknown Category and Packaging lifecycle values", async () => {
    server.use(
      http.get("https://api.zandu.test/api/categories", () =>
        HttpResponse.json([
          {
            id: "category-1",
            organizationId: "org-1",
            name: "Santé",
            parentCategoryId: null,
            status: "DELETED",
            createdAt: "2026-09-14T10:00:00Z",
            updatedAt: null,
            version: 1,
          },
        ]),
      ),
    );
    await expect(api.listCategories(access)).rejects.toBeInstanceOf(ApiContractError);
    server.use(
      http.get("https://api.zandu.test/api/products/product-1/packagings", () =>
        HttpResponse.json([{ ...packaging, status: "REMOVED" }]),
      ),
    );
    await expect(api.listProductPackagings("product-1", access)).rejects.toBeInstanceOf(
      ApiContractError,
    );
  });
  it("supports product details, writes and explicit lifecycle operations", async () => {
    const requests: string[] = [];
    server.use(
      http.get("https://api.zandu.test/api/products/product-1", () => HttpResponse.json(product)),
      http.post("https://api.zandu.test/api/products", async ({ request }) => {
        requests.push(`POST:${JSON.stringify(await request.json())}`);
        return HttpResponse.json(product);
      }),
      http.patch("https://api.zandu.test/api/products/product-1", async ({ request }) => {
        requests.push(`PATCH:${JSON.stringify(await request.json())}`);
        return HttpResponse.json({ ...product, name: "Doliprane" });
      }),
      http.post("https://api.zandu.test/api/products/product-1/deactivate", () =>
        HttpResponse.json({ ...product, status: "INACTIVE" }),
      ),
    );
    await expect(api.getProduct("product-1", access)).resolves.toEqual(product);
    await api.createProduct({
      productCode: "MED-001",
      name: "Paracétamol",
      type: "PHYSICAL",
      baseUnitId: "unit-1",
      inventoryTracked: true,
    });
    await expect(
      api.updateProduct("product-1", { expectedVersion: 1, name: "Doliprane" }),
    ).resolves.toMatchObject({
      name: "Doliprane",
    });
    await expect(api.transitionProduct("product-1", "deactivate")).resolves.toMatchObject({
      status: "INACTIVE",
    });
    expect(requests).toEqual([
      expect.stringContaining("MED-001"),
      'PATCH:{"expectedVersion":1,"name":"Doliprane"}',
    ]);
  });
  it("preserves exact packaging decimals and sends only editable fields on update", async () => {
    server.use(
      http.get("https://api.zandu.test/api/products/product-1/packagings", () =>
        HttpResponse.json([packaging, { ...packaging, id: "foreign", organizationId: "org-2" }]),
      ),
      http.post("https://api.zandu.test/api/products/product-1/packagings", async ({ request }) => {
        expect(await request.json()).toMatchObject({ conversionFactor: "10.000" });
        return HttpResponse.json({
          ...packaging,
          conversionFactor: "10.000",
          minimumQuantity: "0.125",
        });
      }),
      http.patch(
        "https://api.zandu.test/api/products/product-1/packagings/packaging-1",
        async ({ request }) => {
          expect(await request.json()).toEqual({ name: "Carton", minimumQuantity: "0.125" });
          return HttpResponse.json({ ...packaging, name: "Carton", minimumQuantity: "0.125" });
        },
      ),
      http.post(
        "https://api.zandu.test/api/products/product-1/packagings/packaging-1/archive",
        () => HttpResponse.json({ ...packaging, status: "ARCHIVED" }),
      ),
    );
    await expect(api.listProductPackagings("product-1", access)).resolves.toEqual([packaging]);
    await expect(
      api.createProductPackaging("product-1", {
        code: "BOX-10",
        name: "Boîte de 10",
        unitId: "unit-1",
        conversionFactor: "10.000",
        precision: 3,
        minimumQuantity: "0.125",
        quantityIncrement: "0.125",
        allowedForSale: true,
        allowedForPurchase: false,
      }),
    ).resolves.toMatchObject({ conversionFactor: "10.000", minimumQuantity: "0.125" });
    await api.updateProductPackaging("product-1", "packaging-1", {
      name: "Carton",
      minimumQuantity: "0.125",
    });
    await expect(
      api.transitionProductPackaging("product-1", "packaging-1", "archive"),
    ).resolves.toMatchObject({ status: "ARCHIVED" });
  });
  it("supports category create, update, move and lifecycle endpoints", async () => {
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
      http.post("https://api.zandu.test/api/categories", () => HttpResponse.json(category)),
      http.patch("https://api.zandu.test/api/categories/category-1", () =>
        HttpResponse.json({ ...category, name: "Médicaments" }),
      ),
      http.post("https://api.zandu.test/api/categories/category-1/move", async ({ request }) => {
        expect(await request.json()).toEqual({ parentCategoryId: "parent-1" });
        return HttpResponse.json({ ...category, parentCategoryId: "parent-1" });
      }),
      http.post("https://api.zandu.test/api/categories/category-1/archive", () =>
        HttpResponse.json({ ...category, status: "ARCHIVED" }),
      ),
    );
    await expect(api.createCategory({ name: "Santé", parentCategoryId: null })).resolves.toEqual(
      category,
    );
    await expect(api.updateCategory("category-1", { name: "Médicaments" })).resolves.toMatchObject({
      name: "Médicaments",
    });
    await expect(api.moveCategory("category-1", "parent-1")).resolves.toMatchObject({
      parentCategoryId: "parent-1",
    });
    await expect(api.transitionCategory("category-1", "archive")).resolves.toMatchObject({
      status: "ARCHIVED",
    });
  });
});
