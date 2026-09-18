import { cleanup, render, screen, waitFor, within } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import React from "react";
import { afterEach, describe, expect, it } from "vitest";

import { InventoryValuationList } from "../../apps/admin/src/features/inventory/valuations/InventoryValuationList";
import { InventoryValuationsWorkspace } from "../../apps/admin/src/features/inventory/valuations/InventoryValuationsPage";
import { ApiClient, FoundationApi } from "../../packages/api-client/src/index";
import {
  createServerStateClient,
  ServerStateProvider,
} from "../../packages/server-state/src/index";

import type {
  InventoryValuationMovementResource,
  InventoryValuationResource,
  StockResource,
} from "../../packages/api-client/src/index";
import type { EffectiveAccess } from "../../packages/authorization/src/index";

const stock: StockResource = {
  id: "stock-1",
  initialized: true,
  organizationId: "org-1",
  productId: "product-1",
  quantityOnHand: "9007199254740993.125000",
  storeId: "store-1",
  version: 3,
};
const unvaluedStock: StockResource = {
  ...stock,
  id: "stock-2",
  productId: "product-2",
  quantityOnHand: "0.000000",
};
const valuation: InventoryValuationResource = {
  averageUnitCost: "4000.125000000000",
  currency: "XAF",
  id: "valuation-1",
  organizationId: "org-1",
  productId: "product-1",
  quantityOnHand: "9007199254740993.125000",
  stockId: "stock-1",
  storeId: "store-1",
  totalValue: "36028797018963972500.125000",
  version: 4,
};
const movement: InventoryValuationMovementResource = {
  correlationId: "valuation-correlation",
  currency: "XAF",
  id: "valuation-movement-1",
  occurredAt: "2026-09-18T08:00:00Z",
  organizationId: "org-1",
  previousAverageCost: "0.000000000000",
  previousTotalValue: "0.000000",
  productId: "product-1",
  quantity: "9007199254740993.125000",
  resultingAverageCost: "4000.125000000000",
  resultingTotalValue: "36028797018963972500.125000",
  sourceReferenceId: null,
  sourceType: "INITIALIZATION",
  stockId: "stock-1",
  stockMovementId: "stock-movement-1",
  stockValuationId: "valuation-1",
  storeId: "store-1",
  type: "INITIAL_STOCK",
  unitCost: "4000.125000000000",
  value: "36028797018963972500.125000",
};
const access: EffectiveAccess = {
  accessibleStoreIds: ["store-1"],
  authorizationVersion: 1,
  organizationId: "org-1",
  permissions: ["INVENTORY_READ"],
  scope: { storeIds: ["store-1"], type: "SELECTED_STORES" },
};

afterEach(cleanup);

function setup({
  actor = access,
  productId,
  respond,
}: {
  actor?: EffectiveAccess;
  productId?: string;
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
        if (parsed.pathname === "/api/stores/store-1/stocks") {
          return Response.json([stock, unvaluedStock]);
        }
        return Response.json([valuation]);
      },
    }),
  );
  const workspace = (currentActor: EffectiveAccess) => (
    <ServerStateProvider client={client}>
      <InventoryValuationsWorkspace
        access={currentActor}
        api={api}
        currency="XAF"
        locale="fr-FR"
        organizationId="org-1"
        {...(productId === undefined ? {} : { productId })}
        storeId="store-1"
        timeZone="Africa/Lagos"
      />
    </ServerStateProvider>
  );
  const rendered = render(workspace(actor));
  return {
    requests,
    rerenderActor(currentActor: EffectiveAccess) {
      rendered.rerender(workspace(currentActor));
    },
  };
}

describe("Inventory valuation views", () => {
  it("joins Stock with valuations and exposes an explicit uninitialized state", async () => {
    setup({
      respond: (url) =>
        url.pathname === "/api/stores/store-1/stocks"
          ? Response.json([stock, unvaluedStock])
          : Response.json([valuation, { ...valuation, id: "foreign", organizationId: "org-2" }]),
    });
    const table = await screen.findByRole("table");
    const rows = within(table).getAllByRole("row");
    expect(rows).toHaveLength(3);
    expect(rows[1]?.textContent).toContain("Initialisée");
    expect(rows[1]?.textContent).toMatch(/36.028.797.018.963.972.500,125000/);
    expect(rows[2]?.textContent).toContain("Non initialisée");
    expect(rows[2]?.textContent).toContain("0,000000");
    expect(screen.getByText("Devise du magasin : XAF")).toBeTruthy();
  });

  it("shows exact valuation detail and follows the economic ledger cursor", async () => {
    setup({
      productId: "product-1",
      respond: (url) => {
        if (url.pathname.endsWith("/stocks/product-1")) return Response.json(stock);
        if (url.pathname.endsWith("/inventory-valuations/product-1")) {
          return Response.json(valuation);
        }
        if (url.searchParams.get("cursor") === "valuation-next") {
          return Response.json([
            {
              ...movement,
              id: "valuation-movement-2",
              sourceType: "SALE",
              type: "SALE",
            },
          ]);
        }
        return Response.json([movement], { headers: { "X-Next-Cursor": "valuation-next" } });
      },
    });
    expect(await screen.findByRole("heading", { name: "État de la valorisation" })).toBeTruthy();
    expect(screen.getByText(/4.000,125000000000/)).toBeTruthy();
    const ledger = screen.getByRole("heading", { name: "Ledger de valorisation" }).parentElement!;
    expect(await within(ledger).findByText("Stock initial")).toBeTruthy();
    expect(within(ledger).getByText("Entrée")).toBeTruthy();
    expect(within(ledger).getByText("Initialisation du stock")).toBeTruthy();
    await userEvent.setup().click(screen.getByRole("button", { name: "2" }));
    const saleRow = (await screen.findAllByText("Vente"))[0]?.closest("tr");
    expect(saleRow).toBeTruthy();
    expect(saleRow?.textContent).toContain("Sortie");
  });

  it("distinguishes a physical Stock whose valuation is not initialized", async () => {
    const { requests } = setup({
      productId: "product-1",
      respond: (url) => {
        if (url.pathname.endsWith("/stocks/product-1")) return Response.json(stock);
        return Response.json(
          { code: "VALUATION_NOT_INITIALIZED", correlationId: "not-valued" },
          { status: 422 },
        );
      },
    });
    expect(
      await screen.findByRole("heading", { name: "Valorisation non initialisée" }),
    ).toBeTruthy();
    expect(screen.getByText(/Quantité physique/).textContent).toContain(
      "9 007 199 254 740 993,125000",
    );
    expect(requests.some((request) => request.pathname.endsWith("/movements"))).toBe(false);
  });

  it("preserves diagnostics and retries valuation loading", async () => {
    let failed = true;
    setup({
      respond: (url) => {
        if (failed && url.pathname.endsWith("/inventory-valuations")) {
          return Response.json({ correlationId: "valuation-ref" }, { status: 503 });
        }
        return url.pathname.endsWith("/stocks")
          ? Response.json([stock])
          : Response.json([valuation]);
      },
    });
    expect(await screen.findByText("Référence de diagnostic : valuation-ref")).toBeTruthy();
    failed = false;
    await userEvent.setup().click(screen.getByRole("button", { name: "Réessayer" }));
    expect(await screen.findByRole("table")).toBeTruthy();
  });

  it("does not query valuation data outside the effective Store scope", async () => {
    const { requests } = setup({ actor: { ...access, accessibleStoreIds: ["store-2"] } });
    expect(screen.getByRole("heading", { name: "Accès refusé" })).toBeTruthy();
    await waitFor(() => expect(requests).toHaveLength(0));
  });

  it("does not accept invented or operation-only permissions for cost visibility", async () => {
    for (const permission of [
      "INVENTORY_COST_VIEW",
      "INVENTORY_VALUE_VIEW",
      "INVENTORY_COSTING_INITIALIZE",
      "INVENTORY_COST_ASSIGN",
    ]) {
      cleanup();
      const { requests } = setup({ actor: { ...access, permissions: [permission] } });
      expect(screen.getByRole("heading", { name: "Accès refusé" })).toBeTruthy();
      expect(screen.queryByText(/36.028.797.018.963.972.500/)).toBeNull();
      await waitFor(() => expect(requests).toHaveLength(0));
    }
  });

  it("never renders supplied monetary rows while the confidential view is loading", () => {
    render(
      <InventoryValuationList
        currency="XAF"
        isLoading
        locale="fr-FR"
        onRetry={() => undefined}
        rows={[{ stock, valuation }]}
      />,
    );

    expect(screen.getByRole("status", { name: "Chargement des valorisations" })).toBeTruthy();
    expect(screen.queryByText(/36.028.797.018.963.972.500/)).toBeNull();
    expect(screen.queryByRole("columnheader", { name: "Coût moyen" })).toBeNull();
  });

  it("removes already rendered values immediately when read access is revoked", async () => {
    const { requests, rerenderActor } = setup();
    expect(await screen.findByText(/36.028.797.018.963.972.500/)).toBeTruthy();
    expect(requests).toHaveLength(2);

    rerenderActor({ ...access, authorizationVersion: 2, permissions: [] });

    expect(screen.getByRole("heading", { name: "Accès refusé" })).toBeTruthy();
    expect(screen.queryByText(/36.028.797.018.963.972.500/)).toBeNull();
    await waitFor(() => expect(requests).toHaveLength(2));
  });

  it("does not expose server-provided cost details in forbidden errors", async () => {
    setup({
      respond: (url) =>
        url.pathname.endsWith("/stocks")
          ? Response.json([stock])
          : Response.json(
              {
                code: "FORBIDDEN",
                correlationId: "cost-forbidden-ref",
                message: "Confidential totalValue=36028797018963972500.125000",
              },
              { status: 403 },
            ),
    });

    expect(await screen.findByRole("heading", { name: "Action non autorisée" })).toBeTruthy();
    expect(screen.getByText("Référence de diagnostic : cost-forbidden-ref")).toBeTruthy();
    expect(screen.queryByText(/36028797018963972500/)).toBeNull();
    expect(screen.queryByText(/Confidential totalValue/)).toBeNull();
  });
});
