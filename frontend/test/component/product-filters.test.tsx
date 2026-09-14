import { cleanup, render, screen, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import React from "react";
import { afterEach, describe, expect, it } from "vitest";

import { ProductListWorkspace } from "../../apps/admin/src/features/catalog/products/ProductListWorkspace";
import { normalizeProductFilters } from "../../apps/admin/src/features/catalog/schemas/productFilters";
import { ApiClient, FoundationApi } from "../../packages/api-client/src/index";
import {
  createServerStateClient,
  ServerStateProvider,
} from "../../packages/server-state/src/index";

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
  ...{
    organizationId: "org-1",
    authorizationVersion: 1,
    permissions: ["PRODUCT_READ", "CATEGORY_READ"],
    accessibleStoreIds: [],
    scope: { type: "ORGANIZATION" } as const,
  },
  permissions: ["PRODUCT_READ", "CATALOG_READ"],
};
const category = {
  id: "category-1",
  organizationId: "org-1",
  name: "Santé",
  parentCategoryId: null,
  status: "ACTIVE",
  createdAt: "2026-09-14",
  updatedAt: null,
  version: 1,
};
afterEach(cleanup);

function setup({
  permissions = access.permissions,
  categoryFailure = false,
}: { permissions?: string[]; categoryFailure?: boolean } = {}) {
  const requests: URL[] = [];
  const client = createServerStateClient();
  client.setDefaultOptions({ queries: { retry: false } });
  const api = new FoundationApi(
    new ApiClient({
      config: { apiBaseUrl: "https://api.zandu.test/api/", appEnvironment: "test" },
      fetchImplementation: async (url) => {
        const parsed = new URL(url);
        requests.push(parsed);
        if (parsed.pathname === "/api/categories")
          return categoryFailure
            ? Response.json({ correlationId: "category-ref" }, { status: 503 })
            : Response.json([category]);
        return Response.json(
          parsed.searchParams.has("search")
            ? [{ ...product, name: "Résultat serveur" }]
            : [product],
        );
      },
    }),
  );
  const actor = { ...access, permissions };
  const view = render(
    <ServerStateProvider client={client}>
      <ProductListWorkspace key="org-1" api={api} access={actor} organizationId="org-1" />
    </ServerStateProvider>,
  );
  return { requests, view, api, client, actor };
}
describe("Product server filters", () => {
  it("applies combined filters to HTTP and resets the request", async () => {
    const { requests } = setup();
    const user = userEvent.setup();
    await screen.findByRole("option", { name: "Santé" });
    await user.type(screen.getByRole("searchbox", { name: "Rechercher un produit" }), "  A & B  ");
    await user.selectOptions(screen.getByRole("combobox", { name: "Statut" }), "ACTIVE");
    await user.selectOptions(screen.getByRole("combobox", { name: "Type" }), "PHYSICAL");
    await user.selectOptions(screen.getByRole("combobox", { name: "Catégorie" }), "category-1");
    expect(requests.filter((url) => url.pathname === "/api/products")).toHaveLength(1);
    await user.click(screen.getByRole("button", { name: "Appliquer les filtres" }));
    expect(await screen.findByText("Résultat serveur")).toBeTruthy();
    expect(
      Object.fromEntries(
        requests.filter((url) => url.pathname === "/api/products").at(-1)!.searchParams,
      ),
    ).toEqual({ search: "A & B", status: "ACTIVE", type: "PHYSICAL", categoryId: "category-1" });
    await user.click(screen.getByRole("button", { name: "Réinitialiser les filtres" }));
    await screen.findByText("Paracétamol");
    expect(screen.getByRole("searchbox")).toHaveProperty("value", "");
    await waitFor(() =>
      expect(requests.filter((url) => url.pathname === "/api/products").at(-1)!.search).toBe(""),
    );
  });
  it("does not request categories without CATALOG_READ while allowing product search", async () => {
    const { requests } = setup({ permissions: ["PRODUCT_READ"] });
    await screen.findByText("Paracétamol");
    expect(requests.some((url) => url.pathname === "/api/categories")).toBe(false);
    expect(screen.getByRole("combobox", { name: "Catégorie" }).hasAttribute("disabled")).toBe(true);
    expect(screen.getByRole("combobox", { name: "Statut" }).hasAttribute("disabled")).toBe(false);
  });
  it("keeps products usable when loading category options fails", async () => {
    setup({ categoryFailure: true });
    await screen.findByText("Les catégories n’ont pas pu être chargées.");
    expect(await screen.findByText("Paracétamol")).toBeTruthy();
    expect(screen.getByRole("button", { name: "Réessayer les catégories" })).toBeTruthy();
  });
  it("clears filter input and old results on tenant transition", async () => {
    const { view, client, api, actor } = setup();
    const user = userEvent.setup();
    await screen.findByText("Paracétamol");
    await user.type(screen.getByRole("searchbox"), "tenant-one");
    await user.click(screen.getByRole("button", { name: "Appliquer les filtres" }));
    await screen.findByText("Résultat serveur");
    view.rerender(
      <ServerStateProvider client={client}>
        <ProductListWorkspace
          key="org-2"
          api={api}
          access={{ ...actor, organizationId: "org-2" }}
          organizationId="org-2"
        />
      </ServerStateProvider>,
    );
    await screen.findByText("Aucun produit");
    expect(screen.queryByText("Résultat serveur")).toBeNull();
    expect(screen.getByRole("searchbox")).toHaveProperty("value", "");
  });
  it("normalizes only transport values and preserves product codes", () => {
    expect(
      normalizeProductFilters({
        search: "  ",
        status: "",
        type: "SERVICE",
        productCode: "001234",
        categoryId: " category-1 ",
      }),
    ).toEqual({ type: "SERVICE", productCode: "001234", categoryId: "category-1" });
  });
});
