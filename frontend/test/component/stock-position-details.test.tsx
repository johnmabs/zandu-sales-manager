import { cleanup, render, screen, waitFor, within } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import React from "react";
import { afterEach, describe, expect, it, vi } from "vitest";

import { StockPositionDetailsWorkspace } from "../../apps/admin/src/features/inventory/stock-positions/StockPositionDetailsPage";
import { ApiClient, FoundationApi } from "../../packages/api-client/src/index";
import { NotificationCenter } from "../../packages/notifications/src/index";
import {
  createServerStateClient,
  queryKeys,
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

afterEach(() => {
  cleanup();
  vi.restoreAllMocks();
});

function setup({
  actor = access,
  respond,
}: {
  actor?: EffectiveAccess;
  respond?: (url: URL, init: RequestInit) => Response;
} = {}) {
  const requests: URL[] = [];
  const client = createServerStateClient();
  const notifications = new NotificationCenter();
  client.setDefaultOptions({ queries: { retry: false } });
  const api = new FoundationApi(
    new ApiClient({
      config: { apiBaseUrl: "https://api.zandu.test/api/", appEnvironment: "test" },
      fetchImplementation: async (url, init) => {
        const parsed = new URL(url);
        requests.push(parsed);
        if (respond !== undefined) return respond(parsed, init);
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
        currency="XAF"
        locale="fr-FR"
        notifications={notifications}
        organizationId="org-1"
        productId="product-1"
        queryClient={client}
        storeId="store-1"
      />
    </ServerStateProvider>,
  );

  return { client, notifications, requests };
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

  it("initializes an absent position and refreshes every dependent projection", async () => {
    let initialized = false;
    const confirm = vi.spyOn(window, "confirm").mockReturnValue(true);
    const { client, notifications } = setup({
      actor: { ...access, permissions: [...access.permissions, "INVENTORY_INITIALIZE"] },
      respond: (url, init) => {
        if (url.pathname === "/api/products/product-1") return Response.json(product);
        if (url.pathname.endsWith("/initialize")) {
          expect(init.method).toBe("POST");
          expect(JSON.parse(String(init.body))).toEqual({
            quantity: "001.250",
            unitCost: "000800.125000",
          });
          initialized = true;
          return Response.json(
            { ...stock, quantityOnHand: "001.250", version: 1 },
            { status: 201 },
          );
        }
        return Response.json(
          initialized
            ? { ...stock, quantityOnHand: "001.250", version: 1 }
            : { code: "NOT_FOUND", correlationId: "missing-stock" },
          initialized ? undefined : { status: 404 },
        );
      },
    });
    const stockListKey = queryKeys.stock.list("org-1", "store-1");
    const movementListKey = queryKeys.stockMovements.list("org-1", "store-1");
    const valuationListKey = queryKeys.inventoryValuations.list("org-1", "store-1");
    client.setQueryData(stockListKey, []);
    client.setQueryData(movementListKey, []);
    client.setQueryData(valuationListKey, []);
    const user = userEvent.setup();
    await screen.findByRole("form", { name: "Initialiser la position de stock" });
    await user.type(screen.getByLabelText("Quantité initiale"), "001.250");
    await user.type(screen.getByLabelText("Coût unitaire d’ouverture (XAF)"), "000800.125000");
    await user.click(screen.getByRole("button", { name: "Initialiser le stock" }));
    expect(confirm).toHaveBeenCalledWith(expect.stringContaining("001.250"));
    expect(await screen.findByText("1,250")).toBeTruthy();
    expect(notifications.getState().at(-1)?.message).toBe("Stock initialisé.");
    expect(client.getQueryState(stockListKey)?.isInvalidated).toBe(true);
    expect(client.getQueryState(movementListKey)?.isInvalidated).toBe(true);
    expect(client.getQueryState(valuationListKey)?.isInvalidated).toBe(true);
  });

  it("does not fetch outside the effective Store scope", async () => {
    const { requests } = setup({ actor: { ...access, accessibleStoreIds: ["store-2"] } });
    expect(screen.getByRole("heading", { name: "Accès refusé" })).toBeTruthy();
    await waitFor(() => expect(requests).toHaveLength(0));
  });
});
