import { cleanup, render, screen, waitFor, within } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import React from "react";
import { afterEach, describe, expect, it } from "vitest";

import { StockTransferDetailsWorkspace } from "../../apps/admin/src/features/inventory/transfers/StockTransferDetailsPage";
import { StockTransfersWorkspace } from "../../apps/admin/src/features/inventory/transfers/StockTransfersPage";
import { ApiClient, FoundationApi } from "../../packages/api-client/src/index";
import {
  createServerStateClient,
  ServerStateProvider,
} from "../../packages/server-state/src/index";

import type { StockTransferResource } from "../../packages/api-client/src/index";
import type { EffectiveAccess } from "../../packages/authorization/src/index";
import type { AccessibleStore } from "../../packages/store-context/src/index";

const stores: readonly AccessibleStore[] = [
  {
    currency: "XAF",
    id: "store-1",
    locale: "fr-FR",
    name: "Magasin source",
    organizationId: "org-1",
    status: "ACTIVE",
    timeZone: "Africa/Lagos",
  },
  {
    currency: "XAF",
    id: "store-2",
    locale: "fr-FR",
    name: "Magasin destination",
    organizationId: "org-1",
    status: "ACTIVE",
    timeZone: "Africa/Lagos",
  },
];
const receivedTransfer: StockTransferResource = {
  cancellationReason: null,
  cancelledAt: null,
  createdAt: "2026-09-20T08:00:00Z",
  destinationStoreId: "store-2",
  hasTransitDiscrepancy: true,
  id: "transfer-1",
  lines: [
    {
      id: "line-1",
      productId: "product-1",
      receivedQuantity: "0.000000",
      requestedQuantity: "9007199254740993.125000",
      shippedQuantity: "3.000000",
      transitDiscrepancy: "3.000000000000",
    },
  ],
  receivedAt: "2026-09-20T10:00:00Z",
  shippedAt: "2026-09-20T09:00:00Z",
  sourceStoreId: "store-1",
  status: "RECEIVED",
  version: 4,
};
const draftTransfer: StockTransferResource = {
  ...receivedTransfer,
  destinationStoreId: "store-1",
  hasTransitDiscrepancy: false,
  id: "transfer-2",
  lines: [
    {
      ...receivedTransfer.lines[0]!,
      id: "line-2",
      receivedQuantity: null,
      shippedQuantity: null,
      transitDiscrepancy: null,
    },
  ],
  receivedAt: null,
  shippedAt: null,
  sourceStoreId: "store-2",
  status: "DRAFT",
  version: 1,
};
const access: EffectiveAccess = {
  accessibleStoreIds: ["store-1", "store-2"],
  authorizationVersion: 1,
  organizationId: "org-1",
  permissions: ["STOCK_TRANSFER_READ"],
  scope: { storeIds: ["store-1", "store-2"], type: "SELECTED_STORES" },
};

afterEach(cleanup);

function setup({
  actor = access,
  detail,
  respond,
}: {
  actor?: EffectiveAccess;
  detail?: boolean;
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
        return respond?.(parsed) ?? Response.json(detail ? receivedTransfer : [receivedTransfer]);
      },
    }),
  );
  render(
    <ServerStateProvider client={client}>
      {detail ? (
        <StockTransferDetailsWorkspace
          access={actor}
          api={api}
          locale="fr-FR"
          organizationId="org-1"
          storeId="store-1"
          stores={stores}
          timeZone="Africa/Lagos"
          transferId="transfer-1"
        />
      ) : (
        <StockTransfersWorkspace
          access={actor}
          api={api}
          locale="fr-FR"
          organizationId="org-1"
          storeId="store-1"
          stores={stores}
          timeZone="Africa/Lagos"
        />
      )}
    </ServerStateProvider>,
  );
  return { requests };
}

describe("Stock transfer list and details", () => {
  it("renders the server page and follows its opaque cursor", async () => {
    setup({
      respond: (url) => {
        expect(url.pathname).toBe("/api/stock-transfers");
        expect(url.searchParams.get("limit")).toBe("25");
        if (url.searchParams.get("cursor") === "transfer-next") {
          return Response.json([draftTransfer]);
        }
        return Response.json([receivedTransfer], {
          headers: { "X-Next-Cursor": "transfer-next" },
        });
      },
    });

    const firstPage = await screen.findByRole("table");
    const firstRow = within(firstPage).getAllByRole("row")[1];
    expect(firstRow?.textContent).toContain("Magasin source (store-1)");
    expect(firstRow?.textContent).toContain("Magasin destination (store-2)");
    expect(firstRow?.textContent).toContain("Réceptionné");
    expect(firstRow?.textContent).toContain("Oui");
    await userEvent.setup().click(screen.getByRole("button", { name: "2" }));
    expect(await screen.findByText("Brouillon")).toBeTruthy();
  });

  it("keeps requested, shipped and received quantities distinct in the detail", async () => {
    setup({ detail: true });

    expect(await screen.findByRole("heading", { name: "Lignes du transfert" })).toBeTruthy();
    expect(screen.getByText("Statut : Réceptionné")).toBeTruthy();
    const table = screen.getByRole("table");
    const row = within(table).getAllByRole("row")[1];
    expect(row?.textContent).toContain("9 007 199 254 740 993,125000");
    expect(row?.textContent).toContain("3,000000");
    expect(row?.textContent).toContain("0,000000");
    expect(row?.textContent).toContain("3,000000000000");
    expect(screen.getByText("Magasin source (store-1)")).toBeTruthy();
    expect(screen.getByText("Magasin destination (store-2)")).toBeTruthy();

    cleanup();
    setup({
      detail: true,
      respond: () => Response.json({ ...draftTransfer, id: "transfer-1" }),
    });
    const draftTable = await screen.findByRole("table");
    const draftRow = within(draftTable).getAllByRole("row")[1]!;
    expect(within(draftRow).getAllByText("—")).toHaveLength(3);
  });

  it("preserves diagnostics and retries transfer loading", async () => {
    let failed = true;
    setup({
      respond: () => {
        if (failed) return Response.json({ correlationId: "transfer-ref" }, { status: 503 });
        return Response.json([receivedTransfer]);
      },
    });
    expect(await screen.findByText("Référence de diagnostic : transfer-ref")).toBeTruthy();
    failed = false;
    await userEvent.setup().click(screen.getByRole("button", { name: "Réessayer" }));
    expect(await screen.findByRole("table")).toBeTruthy();
  });

  it("projects inaccessible details as absent and never queries without Store scope", async () => {
    setup({
      detail: true,
      respond: () =>
        Response.json({
          ...receivedTransfer,
          destinationStoreId: "store-4",
          sourceStoreId: "store-3",
        }),
    });
    expect(await screen.findByRole("heading", { name: "Transfert introuvable" })).toBeTruthy();
    cleanup();

    const { requests } = setup({
      actor: { ...access, accessibleStoreIds: ["store-2"], scope: { type: "ORGANIZATION" } },
    });
    expect(screen.getByRole("heading", { name: "Accès refusé" })).toBeTruthy();
    await waitFor(() => expect(requests).toHaveLength(0));
  });
});
