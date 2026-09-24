"use client";

import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { can, useEffectiveAccess } from "@zandu/authorization";
import { formatDateTime } from "@zandu/domain-formatting";
import { useNotifications } from "@zandu/notifications/react";
import { useOrganizationContext } from "@zandu/organization-context";
import { queryKeys } from "@zandu/server-state";
import { ErrorState, Spinner } from "@zandu/ui";

import { useAdminRuntime } from "../../../runtime/AdminRuntime";
import {
  addStockTransferLine,
  getStockTransfer,
  removeStockTransferLine,
  shipStockTransfer,
  updateStockTransferLine,
} from "../api/readInventory";
import { hasUnknownInventoryMutationOutcome } from "../mutationSafety";

import { ShipStockTransferForm } from "./ShipStockTransferForm";
import { StockTransferDraftEditor } from "./StockTransferDraftEditor";
import { StockTransferErrorState } from "./StockTransferErrorState";
import { StockTransferLineList } from "./StockTransferLineList";
import { stockTransferStatusLabel, stockTransferStoreLabel } from "./stockTransferPresentation";

import type {
  FoundationApi,
  StockTransferLineCreateInput,
  StockTransferLineUpdateInput,
  StockTransferResource,
  StockTransferShipInput,
} from "@zandu/api-client";
import type { EffectiveAccess } from "@zandu/authorization";
import type { AccessibleStore } from "@zandu/store-context";

export function StockTransferDetailsPage({ transferId }: Readonly<{ transferId: string }>) {
  const access = useEffectiveAccess();
  const { activeOrganizationId } = useOrganizationContext();
  const { api, storeState } = useAdminRuntime();
  const store = storeState?.status === "ACTIVE" ? storeState.activeStore : undefined;

  return (
    <StockTransferDetailsWorkspace
      key={`${activeOrganizationId}:${store?.id}:${transferId}:${access?.authorizationVersion}`}
      access={access}
      api={api}
      locale={store?.locale ?? "fr-FR"}
      organizationId={activeOrganizationId}
      storeId={store?.id}
      stores={storeState?.stores ?? []}
      timeZone={store?.timeZone ?? "UTC"}
      transferId={transferId}
    />
  );
}

export function StockTransferDetailsWorkspace({
  access,
  api,
  locale,
  organizationId,
  storeId,
  stores,
  timeZone,
  transferId,
}: Readonly<{
  access: EffectiveAccess | undefined;
  api: FoundationApi | undefined;
  locale: string;
  organizationId: string | undefined;
  storeId: string | undefined;
  stores: readonly AccessibleStore[];
  timeZone: string;
  transferId: string;
}>) {
  const notifications = useNotifications();
  const queryClient = useQueryClient();
  const allowed =
    organizationId !== undefined &&
    storeId !== undefined &&
    can(access, "STOCK_TRANSFER_READ", { organizationId, storeId });
  const transfer = useQuery({
    enabled: api !== undefined && access !== undefined && allowed,
    queryKey: [
      ...queryKeys.stockTransfers.detail(organizationId ?? "unresolved-organization", transferId),
      { authorizationVersion: access?.authorizationVersion ?? 0 },
    ],
    queryFn: async () => {
      if (api === undefined || access === undefined || !allowed) {
        throw new Error("Le contexte du transfert Stock est indisponible.");
      }
      return (await getStockTransfer(api, transferId, access)) ?? null;
    },
  });
  const editable =
    transfer.data?.status === "DRAFT" &&
    organizationId !== undefined &&
    can(access, "STOCK_TRANSFER_UPDATE", {
      organizationId,
      storeId: transfer.data.sourceStoreId,
    });
  const shippable =
    transfer.data?.status === "DRAFT" &&
    transfer.data.lines.length > 0 &&
    organizationId !== undefined &&
    can(access, "STOCK_TRANSFER_SHIP", {
      organizationId,
      storeId: transfer.data.sourceStoreId,
    }) &&
    can(access, "STOCK_TRANSFER_SHIP", {
      organizationId,
      storeId: transfer.data.destinationStoreId,
    });
  const mayReadProducts =
    organizationId !== undefined && can(access, "PRODUCT_READ", { organizationId });
  const products = useQuery({
    enabled:
      api !== undefined && access !== undefined && (editable || shippable) && mayReadProducts,
    queryKey: queryKeys.products.list(organizationId ?? "unresolved-organization", {
      authorizationVersion: access?.authorizationVersion ?? 0,
      status: "ACTIVE",
    }),
    queryFn: () => {
      if (
        api === undefined ||
        access === undefined ||
        (!editable && !shippable) ||
        !mayReadProducts
      ) {
        throw new Error("Le contexte Produit du transfert est indisponible.");
      }
      return api.listProducts(access, { status: "ACTIVE" });
    },
  });

  const applyTransferUpdate = async (updated: StockTransferResource) => {
    const resolvedOrganizationId = organizationId ?? "unresolved-organization";
    queryClient.setQueriesData(
      { queryKey: queryKeys.stockTransfers.detail(resolvedOrganizationId, transferId) },
      updated,
    );
    await Promise.all([
      queryClient.invalidateQueries({
        queryKey: queryKeys.stockTransfers.list(resolvedOrganizationId, updated.sourceStoreId),
      }),
      queryClient.invalidateQueries({
        queryKey: queryKeys.stockTransfers.list(resolvedOrganizationId, updated.destinationStoreId),
      }),
    ]);
  };
  const invalidateShipmentState = async (item: StockTransferResource) => {
    const resolvedOrganizationId = organizationId ?? "unresolved-organization";
    await Promise.all([
      queryClient.invalidateQueries({
        queryKey: queryKeys.stock.list(resolvedOrganizationId, item.sourceStoreId),
      }),
      queryClient.invalidateQueries({
        queryKey: queryKeys.stockMovements.list(resolvedOrganizationId, item.sourceStoreId),
      }),
      queryClient.invalidateQueries({
        queryKey: queryKeys.inventoryValuations.list(resolvedOrganizationId, item.sourceStoreId),
      }),
      ...item.lines.flatMap((line) => [
        queryClient.invalidateQueries({
          queryKey: queryKeys.stock.detail(
            resolvedOrganizationId,
            item.sourceStoreId,
            line.productId,
          ),
        }),
        queryClient.invalidateQueries({
          queryKey: queryKeys.inventoryValuations.detail(
            resolvedOrganizationId,
            item.sourceStoreId,
            line.productId,
          ),
        }),
        queryClient.invalidateQueries({
          queryKey: queryKeys.inventoryValuations.movements(
            resolvedOrganizationId,
            item.sourceStoreId,
            line.productId,
          ),
        }),
      ]),
    ]);
  };
  const addLine = useMutation({
    mutationFn: (input: StockTransferLineCreateInput) => {
      if (api === undefined || access === undefined || !editable) {
        throw new Error("Le transfert ne peut pas être modifié.");
      }
      return addStockTransferLine(api, transferId, input, access);
    },
    onError: async (error) => {
      if (!hasUnknownInventoryMutationOutcome(error)) return;
      await queryClient.invalidateQueries({
        queryKey: queryKeys.stockTransfers.detail(
          organizationId ?? "unresolved-organization",
          transferId,
        ),
      });
    },
    onSuccess: async (updated) => {
      await applyTransferUpdate(updated);
      notifications.notify({ message: "Ligne ajoutée au transfert.", tone: "success" });
    },
  });
  const updateLine = useMutation({
    mutationFn: ({ lineId, input }: { input: StockTransferLineUpdateInput; lineId: string }) => {
      if (api === undefined || access === undefined || !editable) {
        throw new Error("Le transfert ne peut pas être modifié.");
      }
      return updateStockTransferLine(api, transferId, lineId, input, access);
    },
    onError: async (error) => {
      if (!hasUnknownInventoryMutationOutcome(error)) return;
      await queryClient.invalidateQueries({
        queryKey: queryKeys.stockTransfers.detail(
          organizationId ?? "unresolved-organization",
          transferId,
        ),
      });
    },
    onSuccess: async (updated) => {
      await applyTransferUpdate(updated);
      notifications.notify({ message: "Quantité demandée mise à jour.", tone: "success" });
    },
  });
  const removeLine = useMutation({
    mutationFn: async (lineId: string) => {
      if (api === undefined || !editable) throw new Error("Le transfert ne peut pas être modifié.");
      await removeStockTransferLine(api, transferId, lineId);
    },
    onError: async (error) => {
      if (!hasUnknownInventoryMutationOutcome(error)) return;
      await queryClient.invalidateQueries({
        queryKey: queryKeys.stockTransfers.detail(
          organizationId ?? "unresolved-organization",
          transferId,
        ),
      });
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({
        queryKey: queryKeys.stockTransfers.detail(
          organizationId ?? "unresolved-organization",
          transferId,
        ),
      });
      notifications.notify({ message: "Ligne retirée du transfert.", tone: "success" });
    },
  });
  const shipTransfer = useMutation({
    mutationFn: ({
      idempotencyKey,
      input,
    }: {
      idempotencyKey: string;
      input: StockTransferShipInput;
    }) => {
      if (api === undefined || access === undefined || !shippable || transfer.data == null) {
        throw new Error("Le transfert ne peut pas être expédié.");
      }
      return shipStockTransfer(api, transferId, input, access, idempotencyKey);
    },
    onError: async (error) => {
      if (!hasUnknownInventoryMutationOutcome(error) || transfer.data == null) return;
      await Promise.all([
        queryClient.invalidateQueries({
          queryKey: queryKeys.stockTransfers.detail(
            organizationId ?? "unresolved-organization",
            transferId,
          ),
        }),
        invalidateShipmentState(transfer.data),
      ]);
    },
    onSuccess: async (updated) => {
      await Promise.all([applyTransferUpdate(updated), invalidateShipmentState(updated)]);
      notifications.notify({ message: "Transfert expédié.", tone: "success" });
    },
  });

  if (!allowed) {
    return (
      <ErrorState
        title="Accès refusé"
        description="Votre accès ne permet pas de consulter ce transfert de stock."
      />
    );
  }
  if (transfer.isLoading) return <Spinner label="Chargement du transfert de stock" />;
  if (transfer.error !== null) {
    return (
      <StockTransferErrorState error={transfer.error} onRetry={() => void transfer.refetch()} />
    );
  }
  if (transfer.data === null || transfer.data === undefined) {
    return (
      <ErrorState
        title="Transfert introuvable"
        description="Ce transfert n’existe pas dans l’organisation active ou n’est pas accessible."
      />
    );
  }

  const item = transfer.data;
  const date = (value: string | null) =>
    value === null ? "—" : formatDateTime(value, { locale, timeZone });
  return (
    <div className="zandu-page-stack">
      <a href="/admin/inventory/transfers">Retour aux transferts</a>
      <header>
        <p className="zandu-eyebrow">Transfert de stock</p>
        <h2>{item.id}</h2>
        <p>Statut : {stockTransferStatusLabel(item.status)}</p>
      </header>
      <section aria-labelledby="stock-transfer-general">
        <h3 id="stock-transfer-general">Informations générales</h3>
        <dl>
          <dt>Magasin source</dt>
          <dd>{stockTransferStoreLabel(item.sourceStoreId, stores)}</dd>
          <dt>Magasin destination</dt>
          <dd>{stockTransferStoreLabel(item.destinationStoreId, stores)}</dd>
          <dt>Créé le</dt>
          <dd>{date(item.createdAt)}</dd>
          <dt>Expédié le</dt>
          <dd>{date(item.shippedAt)}</dd>
          <dt>Réceptionné le</dt>
          <dd>{date(item.receivedAt)}</dd>
          <dt>Annulé le</dt>
          <dd>{date(item.cancelledAt)}</dd>
          <dt>Motif d’annulation</dt>
          <dd>{item.cancellationReason ?? "—"}</dd>
          <dt>Écart de transit</dt>
          <dd>{item.hasTransitDiscrepancy ? "Oui" : "Non"}</dd>
          <dt>Version</dt>
          <dd>{item.version}</dd>
        </dl>
      </section>
      <section aria-labelledby="stock-transfer-lines">
        <h3 id="stock-transfer-lines">Lignes du transfert</h3>
        <StockTransferLineList lines={item.lines} locale={locale} />
      </section>
      {editable ? (
        products.isLoading ? (
          <Spinner label="Chargement des produits du transfert" />
        ) : products.error !== null ? (
          <StockTransferErrorState error={products.error} onRetry={() => void products.refetch()} />
        ) : (
          <StockTransferDraftEditor
            lines={item.lines}
            onAdd={(input) => addLine.mutateAsync(input).then(() => undefined)}
            onRemove={(lineId) => removeLine.mutateAsync(lineId)}
            onUpdate={(lineId, input) =>
              updateLine.mutateAsync({ input, lineId }).then(() => undefined)
            }
            {...(mayReadProducts ? { products: products.data ?? [] } : {})}
            version={item.version}
          />
        )
      ) : null}
      {shippable ? (
        products.isLoading ? (
          <Spinner label="Chargement des produits à expédier" />
        ) : products.error !== null ? (
          <StockTransferErrorState error={products.error} onRetry={() => void products.refetch()} />
        ) : (
          <ShipStockTransferForm
            lines={item.lines}
            onShip={(input, idempotencyKey) =>
              shipTransfer.mutateAsync({ idempotencyKey, input }).then(() => undefined)
            }
            productNames={new Map(products.data?.map((product) => [product.id, product.name]))}
            transferId={item.id}
          />
        )
      ) : null}
    </div>
  );
}
