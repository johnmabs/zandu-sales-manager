import { cleanup, render, screen, waitFor, within } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import React from "react";
import { afterEach, describe, expect, it } from "vitest";

import { StockPositionsWorkspace } from "../../apps/admin/src/features/inventory/stock-positions/StockPositionsWorkspace";
import { ApiClient, FoundationApi } from "../../packages/api-client/src/index";
import {
  createServerStateClient,
  ServerStateProvider,
} from "../../packages/server-state/src/index";

import type { ProductResource, StockResource } from "../../packages/api-client/src/index";
import type { EffectiveAccess } from "../../packages/authorization/src/index";

const products: readonly ProductResource[] = [
  {
    activatedAt: "2026-09-14T10:00:00Z",
    baseUnitId: "unit-1",
    categoryId: "category-1",
    createdAt: "2026-09-14T10:00:00Z",
    description: null,
    id: "product-1",
    inventoryTracked: true,
    name: "Paracétamol",
    organizationId: "org-1",
    productCode: "MED-001",
    status: "ACTIVE",
    taxCategoryId: null,
    type: "PHYSICAL",
    updatedAt: null,
    version: 1,
  },
  {
    activatedAt: "2026-09-14T10:00:00Z",
    baseUnitId: "unit-1",
    categoryId: null,
    createdAt: "2026-09-14T10:00:00Z",
    description: null,
    id: "product-2",
    inventoryTracked: true,
    name: "Bandage",
    organizationId: "org-1",
    productCode: "MED-002",
    status: "ACTIVE",
    taxCategoryId: null,
    type: "PHYSICAL",
    updatedAt: null,
    version: 1,
  },
];

const stocks: readonly StockResource[] = [
  {
    id: "stock-2",
    initialized: true,
    organizationId: "org-1",
    productId: "product-2",
    quantityOnHand: "2.000000",
    storeId: "store-1",
    version: 2,
  },
  {
    id: "stock-1",
    initialized: true,
    organizationId: "org-1",
    productId: "product-1",
    quantityOnHand: "1234.500000",
    storeId: "store-1",
    version: 3,
  },
];

const access: EffectiveAccess = {
  accessibleStoreIds: ["store-1"],
  authorizationVersion: 1,
  organizationId: "org-1",
  permissions: ["INVENTORY_READ", "PRODUCT_READ"],
  scope: { storeIds: ["store-1"], type: "SELECTED_STORES" },
};

afterEach(cleanup);

function setup({
  actor = access,
  respond,
}: {
  actor?: EffectiveAccess;
  respond?: (url: URL) => Response;
} = {}) {
  const requests: URL[] = [];
  const client = createServerStateClient();
  client.setDefaultOptions({ queries: { retry: false } });
  const api = new FoundationApi(
    new ApiClient({
      config: { apiBaseUrl: "https://api.zandu.test/api/", appEnvironment: "test" },
      fetchImplementation: async (url) => {
        const parsed = new URL(url);
        requests.push(parsed);
        if (respond !== undefined) return respond(parsed);
        return parsed.pathname === "/api/products"
          ? Response.json([...products].reverse())
          : Response.json(stocks);
      },
    }),
  );

  render(
    <ServerStateProvider client={client}>
      <StockPositionsWorkspace
        access={actor}
        api={api}
        locale="fr-FR"
        organizationId="org-1"
        storeId="store-1"
      />
    </ServerStateProvider>,
  );

  return { requests };
}

describe("Stock positions workspace", () => {
  it("enriches positions while preserving the Stock server order and exact quantity", async () => {
    setup();
    const table = await screen.findByRole("table");
    const rows = within(table).getAllByRole("row");
    expect(rows[1]?.textContent).toContain("Bandage");
    expect(rows[2]?.textContent).toContain("Paracétamol");
    expect(rows[2]?.textContent).toContain("1 234,500000");
    expect(within(rows[2]!).getByRole("link").getAttribute("href")).toBe(
      "/admin/inventory/positions/product-1",
    );
  });

  it("sends product filters to Catalog and intersects without reordering Stock", async () => {
    const { requests } = setup({
      respond: (url) => {
        if (url.pathname !== "/api/products") return Response.json(stocks);
        return Response.json(url.searchParams.get("search") === "para" ? [products[0]] : products);
      },
    });
    const user = userEvent.setup();
    await screen.findByText("Bandage");
    await user.type(screen.getByRole("searchbox", { name: "Rechercher un produit" }), " para ");
    await user.click(screen.getByRole("button", { name: "Appliquer les filtres" }));
    expect(await screen.findByText("Paracétamol")).toBeTruthy();
    expect(screen.queryByText("Bandage")).toBeNull();
    expect(
      requests
        .filter((request) => request.pathname === "/api/products")
        .at(-1)
        ?.searchParams.get("search"),
    ).toBe("para");
  });

  it("keeps inventory readable without Product metadata permission", async () => {
    const { requests } = setup({ actor: { ...access, permissions: ["INVENTORY_READ"] } });
    expect(await screen.findAllByRole("link", { name: "Métadonnées indisponibles" })).toHaveLength(
      2,
    );
    expect(screen.getByText("product-1")).toBeTruthy();
    expect(screen.getByRole("searchbox").hasAttribute("disabled")).toBe(true);
    expect(requests.some((request) => request.pathname === "/api/products")).toBe(false);
  });

  it("preserves diagnostics and retries a failed Stock request", async () => {
    let failed = true;
    setup({
      respond: (url) => {
        if (url.pathname === "/api/products") return Response.json(products);
        if (failed) return Response.json({ correlationId: "stock-ref" }, { status: 503 });
        return Response.json(stocks);
      },
    });
    expect(await screen.findByText("Référence de diagnostic : stock-ref")).toBeTruthy();
    failed = false;
    await userEvent.setup().click(screen.getByRole("button", { name: "Réessayer" }));
    expect(await screen.findByText("Bandage")).toBeTruthy();
  });

  it("does not fetch a store outside the effective access scope", async () => {
    const { requests } = setup({ actor: { ...access, accessibleStoreIds: ["store-2"] } });
    await waitFor(() => expect(requests).toHaveLength(0));
  });
});
