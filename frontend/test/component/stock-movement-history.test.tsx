import { cleanup, render, screen, waitFor, within } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import React from "react";
import { afterEach, describe, expect, it } from "vitest";

import { StockMovementHistoryWorkspace } from "../../apps/admin/src/features/inventory/stock-movements/StockMovementHistoryPage";
import { ApiClient, FoundationApi } from "../../packages/api-client/src/index";
import {
  createServerStateClient,
  ServerStateProvider,
} from "../../packages/server-state/src/index";

import type { StockMovementResource } from "../../packages/api-client/src/index";
import type { EffectiveAccess } from "../../packages/authorization/src/index";

const initial: StockMovementResource = {
  id: "movement-1",
  occurredAt: "2026-09-18T08:00:00Z",
  previousQuantity: "0.000000",
  productId: "product-1",
  quantity: "9007199254740993.125000",
  reason: "Ouverture",
  resultingQuantity: "9007199254740993.125000",
  source: "INITIALIZATION",
  stockId: "stock-1",
  storeId: "store-1",
  type: "INITIAL_STOCK",
};
const sale: StockMovementResource = {
  ...initial,
  id: "movement-2",
  occurredAt: "2026-09-18T09:00:00Z",
  previousQuantity: "9007199254740993.125000",
  quantity: "0000000000000001.125000",
  reason: null,
  resultingQuantity: "9007199254740992.000000",
  source: "SALE",
  type: "SALE",
};
const adjustment: StockMovementResource = {
  ...initial,
  id: "movement-3",
  occurredAt: "2026-09-18T10:00:00Z",
  previousQuantity: "9007199254740992.000000",
  productId: "product-2",
  quantity: "1.500000",
  reason: "Réassort",
  resultingQuantity: "9007199254740993.500000",
  source: "MANUAL_ADJUSTMENT",
  type: "ADJUSTMENT_IN",
};
const access: EffectiveAccess = {
  accessibleStoreIds: ["store-1"],
  authorizationVersion: 1,
  organizationId: "org-1",
  permissions: ["STOCK_MOVEMENT_READ"],
  scope: { storeIds: ["store-1"], type: "SELECTED_STORES" },
};

afterEach(cleanup);

function setup({
  actor = access,
  initialProductId,
  respond,
}: {
  actor?: EffectiveAccess;
  initialProductId?: string;
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
        return respond?.(parsed) ?? Response.json([initial, sale]);
      },
    }),
  );
  render(
    <ServerStateProvider client={client}>
      <StockMovementHistoryWorkspace
        access={actor}
        api={api}
        {...(initialProductId === undefined ? {} : { initialProductId })}
        locale="fr-FR"
        organizationId="org-1"
        storeId="store-1"
        timeZone="Africa/Lagos"
      />
    </ServerStateProvider>,
  );
  return { requests };
}

describe("Stock movement history", () => {
  it("renders the append-only ledger and follows the opaque server cursor", async () => {
    setup({
      initialProductId: "product-1",
      respond: (url) => {
        expect(url.pathname).toBe("/api/stores/store-1/stocks/product-1/movements");
        expect(url.searchParams.get("limit")).toBe("25");
        if (url.searchParams.get("cursor") === "opaque-next")
          return Response.json([{ ...adjustment, productId: "product-1" }]);
        return Response.json([initial, sale], {
          headers: { "X-Next-Cursor": "opaque-next" },
        });
      },
    });
    const table = await screen.findByRole("table");
    const rows = within(table).getAllByRole("row");
    expect(rows[1]?.textContent).toContain("Stock initial");
    expect(rows[1]?.textContent).toContain("Entrée");
    expect(rows[1]?.textContent).toContain("9 007 199 254 740 993,125000");
    expect(rows[2]?.textContent).toContain("Vente");
    expect(rows[2]?.textContent).toContain("Sortie");
    expect(rows[2]?.textContent).toContain("—");
    await userEvent.setup().click(screen.getByRole("button", { name: "2" }));
    expect(await screen.findByText("Ajustement entrant")).toBeTruthy();
    expect(screen.getByRole("button", { name: "2" }).getAttribute("aria-current")).toBe("page");
  });

  it("applies and resets the Product filter through server endpoints", async () => {
    const { requests } = setup();
    const user = userEvent.setup();
    await screen.findByRole("table");
    await user.type(screen.getByLabelText("Identifiant produit"), "  product-2  ");
    await user.click(screen.getByRole("button", { name: "Appliquer le filtre" }));
    await waitFor(() =>
      expect(requests.at(-1)?.pathname).toBe("/api/stores/store-1/stocks/product-2/movements"),
    );
    await user.click(screen.getByRole("button", { name: "Réinitialiser le filtre" }));
    await waitFor(() =>
      expect(requests.at(-1)?.pathname).toBe("/api/stores/store-1/stock-movements"),
    );
  });

  it("preserves diagnostics and retries a failed ledger request", async () => {
    let failed = true;
    setup({
      respond: () => {
        if (failed) return Response.json({ correlationId: "movement-ref" }, { status: 503 });
        return Response.json([initial]);
      },
    });
    expect(await screen.findByText("Référence de diagnostic : movement-ref")).toBeTruthy();
    failed = false;
    await userEvent.setup().click(screen.getByRole("button", { name: "Réessayer" }));
    expect(await screen.findByText("Stock initial")).toBeTruthy();
  });

  it("does not query a store outside the effective scope", async () => {
    const { requests } = setup({ actor: { ...access, accessibleStoreIds: ["store-2"] } });
    expect(screen.getByRole("heading", { name: "Accès refusé" })).toBeTruthy();
    await waitFor(() => expect(requests).toHaveLength(0));
  });
});
