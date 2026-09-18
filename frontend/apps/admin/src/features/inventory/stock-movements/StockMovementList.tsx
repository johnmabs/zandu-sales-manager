"use client";

import { ApiRequestError } from "@zandu/api-client";
import { formatDateTime, formatQuantity } from "@zandu/domain-formatting";
import { ErrorMapper } from "@zandu/error-contract";
import { Button, EmptyState, ErrorState, Spinner } from "@zandu/ui";

import { AdminTable } from "../../../components/tables/AdminTable";

import {
  stockMovementDirection,
  stockMovementSourceLabel,
  stockMovementTypeLabel,
} from "./stockMovementPresentation";

import type { AdminTableColumn } from "../../../components/tables/AdminTable";
import type { StockMovementPage, StockMovementResource } from "@zandu/api-client";

export function StockMovementList({
  error,
  isLoading,
  locale,
  onPageChange,
  onRetry,
  page,
  pageNumber,
  pageSize,
  timeZone,
}: Readonly<{
  error?: unknown;
  isLoading: boolean;
  locale: string;
  onPageChange: (page: number) => void;
  onRetry: () => void;
  page?: StockMovementPage;
  pageNumber: number;
  pageSize: number;
  timeZone: string;
}>) {
  if (isLoading) return <Spinner label="Chargement des mouvements de stock" />;
  if (error !== null && error !== undefined) {
    const mapped = new ErrorMapper().map(
      error instanceof ApiRequestError ? error.apiError : { kind: "response", status: 500 },
    );
    return (
      <ErrorState
        title={mapped.title}
        description={mapped.message}
        action={
          <>
            {mapped.retryable ? (
              <Button onClick={onRetry} type="button">
                Réessayer
              </Button>
            ) : null}
            {mapped.correlationId === undefined ? null : (
              <p>Référence de diagnostic : {mapped.correlationId}</p>
            )}
          </>
        }
      />
    );
  }
  if (page === undefined || page.items.length === 0) {
    return (
      <EmptyState
        title="Aucun mouvement de stock"
        description="Aucun mouvement ne correspond à ce magasin et à ce filtre Produit."
      />
    );
  }

  const columns: readonly AdminTableColumn<StockMovementResource>[] = [
    {
      id: "occurredAt",
      header: "Date",
      cell: (movement) => formatDateTime(movement.occurredAt, { locale, timeZone }),
    },
    {
      id: "productId",
      header: "Produit",
      cell: (movement) => (
        <a href={`/admin/inventory/positions/${encodeURIComponent(movement.productId)}`}>
          {movement.productId}
        </a>
      ),
    },
    { id: "type", header: "Mouvement", cell: (movement) => stockMovementTypeLabel(movement.type) },
    { id: "direction", header: "Sens", cell: (movement) => stockMovementDirection(movement.type) },
    {
      id: "quantity",
      header: "Quantité",
      cell: (movement) => formatQuantity(movement.quantity, { locale }),
    },
    {
      id: "previousQuantity",
      header: "Avant",
      cell: (movement) => formatQuantity(movement.previousQuantity, { locale }),
    },
    {
      id: "resultingQuantity",
      header: "Après",
      cell: (movement) => formatQuantity(movement.resultingQuantity, { locale }),
    },
    {
      id: "source",
      header: "Source",
      cell: (movement) => stockMovementSourceLabel(movement.source),
    },
    { id: "reason", header: "Raison", cell: (movement) => movement.reason ?? "—" },
  ];
  const totalItems =
    page.nextCursor === undefined
      ? (pageNumber - 1) * pageSize + page.items.length
      : (pageNumber + 1) * pageSize;

  return (
    <AdminTable
      columns={columns}
      emptyTitle="Aucun mouvement de stock"
      emptyDescription="Aucun mouvement ne correspond au filtre courant."
      getRowId={(movement) => movement.id}
      isLoading={false}
      onPageChange={onPageChange}
      page={{ items: page.items, page: pageNumber, pageSize, totalItems }}
      query={{ filters: {}, page: pageNumber }}
    />
  );
}
