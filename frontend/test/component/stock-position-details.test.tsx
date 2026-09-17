import { cleanup, render, screen, waitFor, within } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import React from "react";
import { afterEach, describe, expect, it } from "vitest";

import { StockPositionDetailsWorkspace } from "../../apps/admin/src/features/inventory/stock-positions/StockPositionDetailsPage";
import { ApiClient, FoundationApi } from "../../packages/api-client/src/index";
import {
  createServerStateClient,
  ServerStateProvider,
} from "../../packages/server-state/src/index";

import type { ProductResource, StockResource } from "../../packages/api-client/src/index";
import type { EffectiveAccess } from "../../packages/authorization/src/index";

const stock: StockResource = {
  id: "stock-1",
  initialized: true,
  organizationId: "org-1",
  productId: "product-1",
  quantityOnHand: "1234.500000",
  storeId: "store-1",
  version: 7,
};

const product: ProductResource = {
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
};

const access: EffectiveAccess = {
  accessibleStoreIds: ["store-1"],
  authorizationVersion: 1,
  organizationId: "org-1",
  permissions: ["INVENTORY_READ", "PRODUCT_READ", "STOCK_MOVEMENT_READ"],
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
        return parsed.pathname === "/api/products/product-1"
          ? Response.json(product)
          : Response.json(stock);
      },
    }),
  );

  render(
    <ServerStateProvider client={client}>
      <StockPositionDetailsWorkspace
        access={actor}
        api={api}
        locale="fr-FR"
        organizationId="org-1"
        productId="product-1"
        storeId="store-1"
      />
    </ServerStateProvider>,
  );

  return { requests };
}

describe("Stock position details", () => {
  it("shows exact stock data, Product metadata and related links", async () => {
    setup();
    expect(await screen.findByRole("heading", { name: "Paracétamol" })).toBeTruthy();
    const details = screen.getByRole("heading", { name: "État de la position" }).parentElement!;
    expect(details.textContent).toContain("1 234,500000");
    expect(details.textContent).toContain("Initialisée");
    expect(details.textContent).toContain("7");
    expect(screen.getByText("MED-001")).toBeTruthy();
    const related = screen.getByRole("navigation", { name: "Ressources liées à la position" });
    expect(
      within(related).getByRole("link", { name: "Voir les mouvements" }).getAttribute("href"),
    ).toBe("/admin/inventory/movements?productId=product-1");
    expect(
      within(related).getByRole("link", { name: "Voir la valorisation" }).getAttribute("href"),
    ).toBe("/admin/inventory/valuations?productId=product-1");
  });

  it("distinguishes an uninitialized zero position", async () => {
    setup({
      respond: (url) =>
        url.pathname === "/api/products/product-1"
          ? Response.json(product)
          : Response.json({ ...stock, initialized: false, quantityOnHand: "0.000000" }),
    });
    expect(await screen.findByText("À initialiser")).toBeTruthy();
    expect(screen.getByText("0,000000")).toBeTruthy();
  });

  it("keeps the position readable without Product metadata or movement permission", async () => {
    const { requests } = setup({ actor: { ...access, permissions: ["INVENTORY_READ"] } });
    expect(await screen.findByRole("heading", { name: "Produit product-1" })).toBeTruthy();
    expect(screen.queryByRole("link", { name: "Voir les mouvements" })).toBeNull();
    expect(screen.getByRole("link", { name: "Voir la valorisation" })).toBeTruthy();
    expect(requests.some((request) => request.pathname === "/api/products/product-1")).toBe(false);
  });

  it("projects a foreign Stock position as absent without loading Product metadata", async () => {
    const { requests } = setup({
      respond: () => Response.json({ ...stock, organizationId: "org-2" }),
    });
    expect(await screen.findByRole("heading", { name: "Position introuvable" })).toBeTruthy();
    expect(requests).toHaveLength(1);
  });

  it("preserves diagnostics and retries the Stock request", async () => {
    let failed = true;
    setup({
      respond: (url) => {
        if (url.pathname === "/api/products/product-1") return Response.json(product);
        if (failed) return Response.json({ correlationId: "stock-detail-ref" }, { status: 503 });
        return Response.json(stock);
      },
    });
    expect(await screen.findByText("Référence de diagnostic : stock-detail-ref")).toBeTruthy();
    failed = false;
    await userEvent.setup().click(screen.getByRole("button", { name: "Réessayer" }));
    expect(await screen.findByRole("heading", { name: "Paracétamol" })).toBeTruthy();
  });

  it("does not fetch outside the effective Store scope", async () => {
    const { requests } = setup({ actor: { ...access, accessibleStoreIds: ["store-2"] } });
    expect(screen.getByRole("heading", { name: "Accès refusé" })).toBeTruthy();
    await waitFor(() => expect(requests).toHaveLength(0));
  });
});
