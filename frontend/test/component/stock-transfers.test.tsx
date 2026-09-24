import { cleanup, render, screen, waitFor, within } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import React from "react";
import { afterEach, describe, expect, it, vi } from "vitest";

import { CreateStockTransferWorkspace } from "../../apps/admin/src/features/inventory/transfers/CreateStockTransferPage";
import { ReceiveStockTransferForm } from "../../apps/admin/src/features/inventory/transfers/ReceiveStockTransferForm";
import { ShipStockTransferForm } from "../../apps/admin/src/features/inventory/transfers/ShipStockTransferForm";
import { StockTransferDetailsWorkspace } from "../../apps/admin/src/features/inventory/transfers/StockTransferDetailsPage";
import { StockTransfersWorkspace } from "../../apps/admin/src/features/inventory/transfers/StockTransfersPage";
import { ApiClient, ApiRequestError, FoundationApi } from "../../packages/api-client/src/index";
import { NotificationProvider } from "../../packages/notifications/src/react";
import {
  createServerStateClient,
  queryKeys,
  ServerStateProvider,
} from "../../packages/server-state/src/index";

import type { ProductResource, StockTransferResource } from "../../packages/api-client/src/index";
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
const shippedTransfer: StockTransferResource = {
  ...receivedTransfer,
  hasTransitDiscrepancy: false,
  lines: receivedTransfer.lines.map((line) => ({
    ...line,
    receivedQuantity: null,
    transitDiscrepancy: null,
  })),
  receivedAt: null,
  status: "SHIPPED",
  version: 3,
};
const access: EffectiveAccess = {
  accessibleStoreIds: ["store-1", "store-2"],
  authorizationVersion: 1,
  organizationId: "org-1",
  permissions: ["STOCK_TRANSFER_READ"],
  scope: { storeIds: ["store-1", "store-2"], type: "SELECTED_STORES" },
};
const writeAccess: EffectiveAccess = {
  ...access,
  permissions: [
    "PRODUCT_READ",
    "STOCK_TRANSFER_CREATE",
    "STOCK_TRANSFER_READ",
    "STOCK_TRANSFER_RECEIVE",
    "STOCK_TRANSFER_SHIP",
    "STOCK_TRANSFER_UPDATE",
  ],
};
const products: readonly ProductResource[] = [
  {
    activatedAt: "2026-09-14T10:00:00Z",
    baseUnitId: "unit-1",
    categoryId: null,
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

afterEach(() => {
  cleanup();
  vi.restoreAllMocks();
});

function setup({
  actor = access,
  detail,
  respond,
}: {
  actor?: EffectiveAccess;
  detail?: boolean;
  respond?: (url: URL, init?: RequestInit) => Response | Promise<Response>;
} = {}) {
  const requests: URL[] = [];
  const client = createServerStateClient();
  client.setDefaultOptions({ queries: { retry: false } });
  const api = new FoundationApi(
    new ApiClient({
      config: { apiBaseUrl: "https://api.zandu.test/api/", appEnvironment: "test" },
      fetchImplementation: async (url, init) => {
        const parsed = new URL(url);
        requests.push(parsed);
        return (
          (await respond?.(parsed, init)) ??
          Response.json(detail ? receivedTransfer : [receivedTransfer])
        );
      },
    }),
  );
  render(
    <ServerStateProvider client={client}>
      <NotificationProvider>
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
      </NotificationProvider>
    </ServerStateProvider>,
  );
  return { client, requests };
}

function setupCreate({
  onCreated,
  respond,
}: {
  onCreated: (transfer: StockTransferResource) => void;
  respond: (url: URL, init: RequestInit) => Response | Promise<Response>;
}) {
  const client = createServerStateClient();
  client.setDefaultOptions({ mutations: { retry: false }, queries: { retry: false } });
  const api = new FoundationApi(
    new ApiClient({
      config: { apiBaseUrl: "https://api.zandu.test/api/", appEnvironment: "test" },
      fetchImplementation: async (url, init) => respond(new URL(url), init),
    }),
  );
  render(
    <ServerStateProvider client={client}>
      <CreateStockTransferWorkspace
        access={writeAccess}
        activeStoreId="store-1"
        api={api}
        onCreated={onCreated}
        organizationId="org-1"
        queryClient={client}
        stores={stores}
      />
    </ServerStateProvider>,
  );
}

function payloadString(payload: unknown, field: string): string {
  if (typeof payload !== "object" || payload === null) {
    throw new Error(`Expected ${field} to be a string.`);
  }
  const value = Object.entries(payload).find(([key]) => key === field)?.[1];
  if (typeof value !== "string") throw new Error(`Expected ${field} to be a string.`);
  return value;
}

describe("Stock transfer list and details", () => {
  it("creates a draft between two distinct accessible stores", async () => {
    const onCreated = vi.fn();
    setupCreate({
      onCreated,
      respond: async (url, init) => {
        expect(url.pathname).toBe("/api/stock-transfers");
        await expect(JSON.parse(String(init.body))).toEqual({
          destinationStoreId: "store-2",
          sourceStoreId: "store-1",
        });
        return Response.json(
          {
            ...draftTransfer,
            destinationStoreId: "store-2",
            id: "transfer-created",
            lines: [],
            sourceStoreId: "store-1",
          },
          { status: 201 },
        );
      },
    });
    const user = userEvent.setup();
    const form = screen.getByRole("form", { name: "Créer un transfert de stock" });
    await user.selectOptions(within(form).getByLabelText("Magasin destination"), "store-1");
    await user.click(within(form).getByRole("button", { name: "Créer le transfert" }));
    expect(
      await within(form).findByText(
        "Le magasin destination doit être différent du magasin source.",
      ),
    ).toBeTruthy();
    expect(onCreated).not.toHaveBeenCalled();

    await user.selectOptions(within(form).getByLabelText("Magasin destination"), "store-2");
    await user.click(within(form).getByRole("button", { name: "Créer le transfert" }));
    await waitFor(() =>
      expect(onCreated).toHaveBeenCalledWith(expect.objectContaining({ id: "transfer-created" })),
    );
  });

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

  it("adds, version-updates and removes exact draft lines", async () => {
    let current = {
      ...draftTransfer,
      destinationStoreId: "store-2",
      id: "transfer-1",
      sourceStoreId: "store-1",
    };
    const payloads: unknown[] = [];
    vi.spyOn(window, "confirm").mockReturnValue(true);
    setup({
      actor: writeAccess,
      detail: true,
      respond: async (url, init) => {
        if (url.pathname === "/api/products") return Response.json(products);
        if (url.pathname === "/api/stock-transfers/transfer-1" && init?.method === "GET") {
          return Response.json(current);
        }
        if (
          url.pathname === "/api/stock-transfers/transfer-1/lines/line-2" &&
          init?.method === "PATCH"
        ) {
          const payload = JSON.parse(String(init.body));
          payloads.push(payload);
          current = {
            ...current,
            lines: current.lines.map((line) =>
              line.id === "line-2"
                ? { ...line, requestedQuantity: payloadString(payload, "requestedQuantity") }
                : line,
            ),
            version: 2,
          };
          return Response.json(current);
        }
        if (url.pathname === "/api/stock-transfers/transfer-1/lines" && init?.method === "POST") {
          const payload = JSON.parse(String(init.body));
          payloads.push(payload);
          current = {
            ...current,
            lines: [
              ...current.lines,
              {
                id: "line-3",
                productId: payloadString(payload, "productId"),
                receivedQuantity: null,
                requestedQuantity: payloadString(payload, "requestedQuantity"),
                shippedQuantity: null,
                transitDiscrepancy: null,
              },
            ],
            version: 3,
          };
          return Response.json(current, { status: 201 });
        }
        if (
          url.pathname === "/api/stock-transfers/transfer-1/lines/line-2" &&
          init?.method === "DELETE"
        ) {
          current = { ...current, lines: current.lines.filter((line) => line.id !== "line-2") };
          return new Response(null, { status: 204 });
        }
        throw new Error(`Unexpected request: ${init?.method} ${url.pathname}`);
      },
    });
    const user = userEvent.setup();
    const editForm = await screen.findByRole("form", { name: "Modifier la ligne Paracétamol" });
    const editQuantity = within(editForm).getByLabelText("Quantité demandée");
    await user.clear(editQuantity);
    await user.type(editQuantity, "000.125000");
    await user.click(within(editForm).getByRole("button", { name: "Enregistrer" }));
    await waitFor(() =>
      expect(payloads).toContainEqual({
        expectedVersion: 1,
        requestedQuantity: "000.125000",
      }),
    );

    const addForm = screen.getByRole("form", { name: "Ajouter une ligne au transfert" });
    await user.selectOptions(within(addForm).getByLabelText("Produit"), "product-2");
    await user.type(within(addForm).getByLabelText("Quantité demandée"), "001.250000");
    await user.click(within(addForm).getByRole("button", { name: "Ajouter la ligne" }));
    await waitFor(() =>
      expect(payloads).toContainEqual({
        productId: "product-2",
        requestedQuantity: "001.250000",
      }),
    );

    await user.click(within(editForm).getByRole("button", { name: "Retirer" }));
    await waitFor(() =>
      expect(screen.queryByRole("form", { name: "Modifier la ligne Paracétamol" })).toBeNull(),
    );
  });

  it("confirms and ships all lines with exact quantities and an idempotency key", async () => {
    let current: StockTransferResource = {
      ...draftTransfer,
      destinationStoreId: "store-2",
      id: "transfer-1",
      sourceStoreId: "store-1",
    };
    let shipmentBody: unknown;
    let idempotencyKey: string | null = null;
    vi.spyOn(window, "confirm").mockReturnValue(true);
    const { client } = setup({
      actor: writeAccess,
      detail: true,
      respond: async (url, init) => {
        if (url.pathname === "/api/products") return Response.json(products);
        if (url.pathname === "/api/stock-transfers/transfer-1" && init?.method === "GET") {
          return Response.json(current);
        }
        if (url.pathname === "/api/stock-transfers/transfer-1/ship") {
          shipmentBody = JSON.parse(String(init?.body));
          idempotencyKey = new Headers(init?.headers).get("Idempotency-Key");
          current = {
            ...current,
            lines: current.lines.map((line) => ({
              ...line,
              shippedQuantity: "000.125000",
            })),
            shippedAt: "2026-09-24T08:00:00Z",
            status: "SHIPPED",
            version: 2,
          };
          return Response.json(current);
        }
        throw new Error(`Unexpected request: ${init?.method} ${url.pathname}`);
      },
    });
    const affectedKeys = [
      queryKeys.stock.list("org-1", "store-1"),
      queryKeys.stock.detail("org-1", "store-1", "product-1"),
      queryKeys.stockMovements.list("org-1", "store-1"),
      queryKeys.inventoryValuations.list("org-1", "store-1"),
      queryKeys.inventoryValuations.detail("org-1", "store-1", "product-1"),
      queryKeys.inventoryValuations.movements("org-1", "store-1", "product-1"),
      queryKeys.stockTransfers.list("org-1", "store-1"),
      queryKeys.stockTransfers.list("org-1", "store-2"),
    ] as const;
    for (const key of affectedKeys) client.setQueryData(key, { cached: true });
    const user = userEvent.setup();
    const form = await screen.findByRole("form", { name: "Expédier le transfert" });
    const quantity = within(form).getByLabelText(/Quantité expédiée/);
    await user.clear(quantity);
    await user.type(quantity, "000.125000");
    await user.click(within(form).getByRole("button", { name: "Confirmer l’expédition" }));

    await waitFor(() => expect(screen.getByText("Statut : Expédié")).toBeTruthy());
    expect(window.confirm).toHaveBeenCalledWith(
      "Expédier ce transfert ? Les stocks du magasin source seront diminués immédiatement.",
    );
    expect(shipmentBody).toEqual({
      lines: [{ lineId: "line-2", shippedQuantity: "000.125000" }],
    });
    expect(idempotencyKey).toBeTruthy();
    expect(screen.queryByRole("form", { name: "Expédier le transfert" })).toBeNull();
    for (const key of affectedKeys) {
      expect(client.getQueryState(key)?.isInvalidated).toBe(true);
    }
  });

  it("shows insufficient stock with its diagnostic reference", async () => {
    const current = {
      ...draftTransfer,
      destinationStoreId: "store-2",
      id: "transfer-1",
      sourceStoreId: "store-1",
    };
    vi.spyOn(window, "confirm").mockReturnValue(true);
    setup({
      actor: writeAccess,
      detail: true,
      respond: (url, init) => {
        if (url.pathname === "/api/products") return Response.json(products);
        if (url.pathname === "/api/stock-transfers/transfer-1" && init?.method === "GET") {
          return Response.json(current);
        }
        if (url.pathname === "/api/stock-transfers/transfer-1/ship") {
          return Response.json(
            { code: "TRANSFER_INSUFFICIENT_STOCK", correlationId: "ship-stock-ref" },
            { status: 422 },
          );
        }
        throw new Error(`Unexpected request: ${init?.method} ${url.pathname}`);
      },
    });
    const form = await screen.findByRole("form", { name: "Expédier le transfert" });
    await userEvent
      .setup()
      .click(within(form).getByRole("button", { name: "Confirmer l’expédition" }));
    expect(
      await within(form).findByText("Stock insuffisant pour expédier le transfert."),
    ).toBeTruthy();
    expect(within(form).getByText("Référence de diagnostic : ship-stock-ref")).toBeTruthy();
  });

  it("retries an unknown shipment outcome with the same idempotency key", async () => {
    const keys: string[] = [];
    const onShip = vi
      .fn(async (_input, key: string) => {
        keys.push(key);
      })
      .mockRejectedValueOnce(
        new ApiRequestError(
          { correlationId: "ship-timeout-ref", kind: "network", message: "The request timed out." },
          true,
        ),
      );
    vi.spyOn(window, "confirm").mockReturnValue(true);
    render(
      <ShipStockTransferForm
        lines={draftTransfer.lines}
        onShip={onShip}
        productNames={new Map([["product-1", "Paracétamol"]])}
        transferId="transfer-1"
      />,
    );
    const form = screen.getByRole("form", { name: "Expédier le transfert" });
    await userEvent
      .setup()
      .click(within(form).getByRole("button", { name: "Confirmer l’expédition" }));
    expect(await within(form).findByText(/L’expédition a peut-être été enregistrée/)).toBeTruthy();
    expect(within(form).getByText("Référence de diagnostic : ship-timeout-ref")).toBeTruthy();

    await userEvent
      .setup()
      .click(within(form).getByRole("button", { name: "Réessayer avec la même clé" }));
    await waitFor(() => expect(onShip).toHaveBeenCalledTimes(2));
    expect(onShip.mock.calls[0]?.[1]).toBe(onShip.mock.calls[1]?.[1]);
    expect(keys).toEqual([onShip.mock.calls[1]?.[1]]);
  });

  it("receives actual quantities and displays only the server discrepancy", async () => {
    let current = shippedTransfer;
    let receptionBody: unknown;
    let idempotencyKey: string | null = null;
    vi.spyOn(window, "confirm").mockReturnValue(true);
    const { client } = setup({
      actor: writeAccess,
      detail: true,
      respond: (url, init) => {
        if (url.pathname === "/api/products") return Response.json(products);
        if (url.pathname === "/api/stock-transfers/transfer-1" && init?.method === "GET") {
          return Response.json(current);
        }
        if (url.pathname === "/api/stock-transfers/transfer-1/receive") {
          receptionBody = JSON.parse(String(init?.body));
          idempotencyKey = new Headers(init?.headers).get("Idempotency-Key");
          current = {
            ...current,
            hasTransitDiscrepancy: true,
            lines: current.lines.map((line) => ({
              ...line,
              receivedQuantity: "2.000000",
              transitDiscrepancy: "1.000000000000",
            })),
            receivedAt: "2026-09-24T09:00:00Z",
            status: "RECEIVED",
            version: 4,
          };
          return Response.json(current);
        }
        throw new Error(`Unexpected request: ${init?.method} ${url.pathname}`);
      },
    });
    const affectedKeys = [
      queryKeys.stock.list("org-1", "store-2"),
      queryKeys.stock.detail("org-1", "store-2", "product-1"),
      queryKeys.stockMovements.list("org-1", "store-2"),
      queryKeys.inventoryValuations.list("org-1", "store-2"),
      queryKeys.inventoryValuations.detail("org-1", "store-2", "product-1"),
      queryKeys.inventoryValuations.movements("org-1", "store-2", "product-1"),
      queryKeys.stockTransfers.list("org-1", "store-1"),
      queryKeys.stockTransfers.list("org-1", "store-2"),
    ] as const;
    for (const key of affectedKeys) client.setQueryData(key, { cached: true });
    const user = userEvent.setup();
    const form = await screen.findByRole("form", { name: "Réceptionner le transfert" });
    const quantity = within(form).getByLabelText(/Quantité reçue/);
    await user.clear(quantity);
    await user.type(quantity, "2.000000");
    await user.click(within(form).getByRole("button", { name: "Confirmer la réception" }));

    await waitFor(() => expect(screen.getByText("Statut : Réceptionné")).toBeTruthy());
    expect(window.confirm).toHaveBeenCalledWith(
      "Réceptionner ce transfert ? Seules les quantités réellement reçues seront ajoutées au magasin destination.",
    );
    expect(receptionBody).toEqual({
      lines: [{ lineId: "line-1", receivedQuantity: "2.000000" }],
    });
    expect(idempotencyKey).toBeTruthy();
    const row = within(screen.getByRole("table")).getAllByRole("row")[1]!;
    expect(row.textContent).toContain("2,000000");
    expect(row.textContent).toContain("1,000000000000");
    const general = screen.getByRole("region", { name: "Informations générales" });
    expect(within(general).getByText("Écart de transit").nextElementSibling?.textContent).toBe(
      "Oui",
    );
    for (const key of affectedKeys) {
      expect(client.getQueryState(key)?.isInvalidated).toBe(true);
    }
  });

  it("shows a correlated error when received quantity exceeds shipped quantity", async () => {
    vi.spyOn(window, "confirm").mockReturnValue(true);
    const onReceive = vi.fn().mockRejectedValue(
      new ApiRequestError(
        {
          code: "TRANSFER_RECEIVED_QUANTITY_EXCEEDS_SHIPPED",
          correlationId: "receive-quantity-ref",
          kind: "response",
          status: 422,
        },
        false,
      ),
    );
    render(
      <ReceiveStockTransferForm
        lines={shippedTransfer.lines}
        onReceive={onReceive}
        productNames={new Map([["product-1", "Paracétamol"]])}
        transferId="transfer-1"
      />,
    );
    const form = screen.getByRole("form", { name: "Réceptionner le transfert" });
    const quantity = within(form).getByLabelText(/Quantité reçue/);
    await userEvent.setup().clear(quantity);
    await userEvent.setup().type(quantity, "4");
    await userEvent
      .setup()
      .click(within(form).getByRole("button", { name: "Confirmer la réception" }));
    expect(
      await within(form).findByText("Une quantité reçue dépasse la quantité expédiée."),
    ).toBeTruthy();
    expect(within(form).getByText("Référence de diagnostic : receive-quantity-ref")).toBeTruthy();
  });

  it("retries an unknown reception outcome with the same idempotency key", async () => {
    const onReceive = vi.fn().mockRejectedValueOnce(
      new ApiRequestError(
        {
          correlationId: "receive-timeout-ref",
          kind: "network",
          message: "The request timed out.",
        },
        true,
      ),
    );
    vi.spyOn(window, "confirm").mockReturnValue(true);
    render(
      <ReceiveStockTransferForm
        lines={shippedTransfer.lines}
        onReceive={onReceive}
        transferId="transfer-1"
      />,
    );
    const form = screen.getByRole("form", { name: "Réceptionner le transfert" });
    const quantity = within(form).getByLabelText(/Quantité reçue/);
    await userEvent.setup().clear(quantity);
    await userEvent.setup().type(quantity, "0");
    await userEvent
      .setup()
      .click(within(form).getByRole("button", { name: "Confirmer la réception" }));
    expect(await within(form).findByText(/La réception a peut-être été enregistrée/)).toBeTruthy();
    expect(within(form).getByText("Référence de diagnostic : receive-timeout-ref")).toBeTruthy();

    await userEvent
      .setup()
      .click(within(form).getByRole("button", { name: "Réessayer avec la même clé" }));
    await waitFor(() => expect(onReceive).toHaveBeenCalledTimes(2));
    expect(onReceive.mock.calls[0]?.[0]).toEqual({
      lines: [{ lineId: "line-1", receivedQuantity: "0" }],
    });
    expect(onReceive.mock.calls[0]?.[1]).toBe(onReceive.mock.calls[1]?.[1]);
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
