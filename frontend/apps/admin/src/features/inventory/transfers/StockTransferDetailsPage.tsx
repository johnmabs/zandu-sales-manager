"use client";

import { useQuery } from "@tanstack/react-query";
import { can, useEffectiveAccess } from "@zandu/authorization";
import { formatDateTime } from "@zandu/domain-formatting";
import { useOrganizationContext } from "@zandu/organization-context";
import { queryKeys } from "@zandu/server-state";
import { ErrorState, Spinner } from "@zandu/ui";

import { useAdminRuntime } from "../../../runtime/AdminRuntime";
import { getStockTransfer } from "../api/readInventory";

import { StockTransferErrorState } from "./StockTransferErrorState";
import { StockTransferLineList } from "./StockTransferLineList";
import { stockTransferStatusLabel, stockTransferStoreLabel } from "./stockTransferPresentation";

import type { FoundationApi } from "@zandu/api-client";
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
    </div>
  );
}
