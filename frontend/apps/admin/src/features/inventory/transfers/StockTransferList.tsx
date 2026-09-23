"use client";

import { formatDateTime } from "@zandu/domain-formatting";
import { EmptyState, Spinner } from "@zandu/ui";

import { AdminTable } from "../../../components/tables/AdminTable";

import { StockTransferErrorState } from "./StockTransferErrorState";
import { stockTransferStatusLabel, stockTransferStoreLabel } from "./stockTransferPresentation";

import type { AdminTableColumn } from "../../../components/tables/AdminTable";
import type { StockTransferPage, StockTransferResource } from "@zandu/api-client";
import type { AccessibleStore } from "@zandu/store-context";

export function StockTransferList({
  error,
  isLoading,
  locale,
  onPageChange,
  onRetry,
  page,
  pageNumber,
  pageSize,
  stores,
  timeZone,
}: Readonly<{
  error?: unknown;
  isLoading: boolean;
  locale: string;
  onPageChange: (page: number) => void;
  onRetry: () => void;
  page?: StockTransferPage;
  pageNumber: number;
  pageSize: number;
  stores: readonly AccessibleStore[];
  timeZone: string;
}>) {
  if (isLoading) return <Spinner label="Chargement des transferts de stock" />;
  if (error !== null && error !== undefined)
    return <StockTransferErrorState error={error} onRetry={onRetry} />;
  if (page === undefined || page.items.length === 0) {
    return (
      <EmptyState
        title="Aucun transfert de stock"
        description="Aucun transfert accessible n’est disponible dans cette organisation."
      />
    );
  }

  const columns: readonly AdminTableColumn<StockTransferResource>[] = [
    {
      id: "createdAt",
      header: "Créé le",
      cell: (transfer) => formatDateTime(transfer.createdAt, { locale, timeZone }),
    },
    {
      id: "id",
      header: "Transfert",
      cell: (transfer) => (
        <a href={`/admin/inventory/transfers/${encodeURIComponent(transfer.id)}`}>{transfer.id}</a>
      ),
    },
    {
      id: "sourceStoreId",
      header: "Magasin source",
      cell: (transfer) => stockTransferStoreLabel(transfer.sourceStoreId, stores),
    },
    {
      id: "destinationStoreId",
      header: "Magasin destination",
      cell: (transfer) => stockTransferStoreLabel(transfer.destinationStoreId, stores),
    },
    {
      id: "status",
      header: "Statut",
      cell: (transfer) => stockTransferStatusLabel(transfer.status),
    },
    { id: "lines", header: "Lignes", cell: (transfer) => transfer.lines.length },
    {
      id: "hasTransitDiscrepancy",
      header: "Écart de transit",
      cell: (transfer) => (transfer.hasTransitDiscrepancy ? "Oui" : "Non"),
    },
  ];
  const totalItems =
    page.nextCursor === undefined
      ? (pageNumber - 1) * pageSize + page.items.length
      : (pageNumber + 1) * pageSize;

  return (
    <AdminTable
      columns={columns}
      emptyTitle="Aucun transfert de stock"
      emptyDescription="Aucun transfert accessible n’est disponible."
      getRowId={(transfer) => transfer.id}
      isLoading={false}
      onPageChange={onPageChange}
      page={{ items: page.items, page: pageNumber, pageSize, totalItems }}
      query={{ filters: {}, page: pageNumber }}
    />
  );
}
