import { cleanup, render, screen, waitFor, within } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import React from "react";
import { afterEach, describe, expect, it } from "vitest";

import { useProductList } from "../../apps/admin/src/features/catalog/hooks/useProductList";
import { ProductList } from "../../apps/admin/src/features/catalog/products/ProductList";
import { ApiClient, FoundationApi } from "../../packages/api-client/src/index";
import {
  createServerStateClient,
  ServerStateProvider,
} from "../../packages/server-state/src/index";

import type { ProductResource } from "../../packages/api-client/src/index";
import type { EffectiveAccess } from "../../packages/authorization/src/index";

const product: ProductResource = {
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
const access: EffectiveAccess = {
  organizationId: "org-1",
  authorizationVersion: 1,
  permissions: ["PRODUCT_READ", "CATEGORY_READ"],
  accessibleStoreIds: [],
  scope: { type: "ORGANIZATION" } as const,
};
afterEach(cleanup);
function createApi(respond: (url: string) => Response) {
  return new FoundationApi(
    new ApiClient({
      config: { apiBaseUrl: "https://api.zandu.test/api/", appEnvironment: "test" },
      fetchImplementation: async (url) => respond(url),
    }),
  );
}
function ListHarness({
  api,
  actor = access,
  organizationId = "org-1",
}: {
  api: FoundationApi;
  actor?: EffectiveAccess;
  organizationId?: string;
}) {
  const query = useProductList({ api, access: actor, organizationId });
  return (
    <ProductList
      products={query.data}
      isLoading={query.isLoading}
      error={query.error}
      onRetry={() => void query.refetch()}
    />
  );
}
function client() {
  const cache = createServerStateClient();
  cache.setDefaultOptions({ queries: { retry: false } });
  return cache;
}
describe("Product list", () => {
  it("renders the server order without frontend sorting or pagination", async () => {
    const api = createApi(() =>
      Response.json([{ ...product, id: "second", productCode: "ZZZ" }, product]),
    );
    render(
      <ServerStateProvider client={client()}>
        <ListHarness api={api} />
      </ServerStateProvider>,
    );
    expect(screen.getByRole("status").getAttribute("aria-label")).toBe("Chargement des produits");
    const table = await screen.findByRole("table");
    const rows = within(table).getAllByRole("row");
    expect(rows[1]?.textContent).toContain("ZZZ");
    expect(rows[2]?.textContent).toContain("MED-001");
  });
  it("shows an explicit empty result", async () => {
    render(
      <ServerStateProvider client={client()}>
        <ListHarness api={createApi(() => Response.json([]))} />
      </ServerStateProvider>,
    );
    expect(await screen.findByText("Aucun produit")).toBeTruthy();
  });
  it("preserves diagnostics and retries a failed query", async () => {
    let failed = true;
    const api = createApi(() =>
      failed
        ? Response.json({ correlationId: "ref-product" }, { status: 500 })
        : Response.json([product]),
    );
    render(
      <ServerStateProvider client={client()}>
        <ListHarness api={api} />
      </ServerStateProvider>,
    );
    expect(await screen.findByText("Référence de diagnostic : ref-product")).toBeTruthy();
    failed = false;
    await userEvent.setup().click(screen.getByRole("button", { name: "Réessayer" }));
    expect(await screen.findByText("Paracétamol")).toBeTruthy();
  });
  it("does not fetch without permission or for a mismatched tenant", async () => {
    const requests: string[] = [];
    const api = createApi((url) => {
      requests.push(url);
      return Response.json([product]);
    });
    const view = render(
      <ServerStateProvider client={client()}>
        <ListHarness api={api} actor={{ ...access, permissions: [] }} />
      </ServerStateProvider>,
    );
    await waitFor(() => expect(requests).toHaveLength(0));
    view.rerender(
      <ServerStateProvider client={client()}>
        <ListHarness api={api} organizationId="foreign" />
      </ServerStateProvider>,
    );
    expect(requests).toHaveLength(0);
  });
  it("separates cached data by tenant and authorization version", async () => {
    const cache = client();
    const api = createApi(() => Response.json([product]));
    const view = render(
      <ServerStateProvider client={cache}>
        <ListHarness api={api} />
      </ServerStateProvider>,
    );
    await screen.findByText("Paracétamol");
    view.rerender(
      <ServerStateProvider client={cache}>
        <ListHarness api={api} actor={{ ...access, authorizationVersion: 2 }} />
      </ServerStateProvider>,
    );
    await waitFor(() => expect(cache.getQueryCache().getAll()).toHaveLength(2));
    view.rerender(
      <ServerStateProvider client={cache}>
        <ListHarness
          api={api}
          actor={{ ...access, organizationId: "org-2" }}
          organizationId="org-2"
        />
      </ServerStateProvider>,
    );
    await screen.findByText("Aucun produit");
    expect(screen.queryByText("Paracétamol")).toBeNull();
  });
});
