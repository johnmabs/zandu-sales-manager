"use client";

import { formatDateTime, formatMoney, formatQuantity } from "@zandu/domain-formatting";
import { EmptyState, Spinner } from "@zandu/ui";

import { AdminTable } from "../../../components/tables/AdminTable";

import { ValuationLoadError } from "./InventoryValuationList";
import {
  valuationMovementDirection,
  valuationMovementSourceLabel,
  valuationMovementTypeLabel,
} from "./valuationPresentation";

import type { AdminTableColumn } from "../../../components/tables/AdminTable";
import type {
  InventoryValuationMovementPage,
  InventoryValuationMovementResource,
} from "@zandu/api-client";

export function InventoryValuationMovementList({
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
  page?: InventoryValuationMovementPage;
  pageNumber: number;
  pageSize: number;
  timeZone: string;
}>) {
  if (isLoading) return <Spinner label="Chargement du ledger de valorisation" />;
  if (error !== null && error !== undefined)
    return <ValuationLoadError error={error} onRetry={onRetry} />;
  if (page === undefined || page.items.length === 0) {
    return (
      <EmptyState
        title="Aucun mouvement de valorisation"
        description="Aucun mouvement économique n’est enregistré pour cette position."
      />
    );
  }

  const columns: readonly AdminTableColumn<InventoryValuationMovementResource>[] = [
    {
      id: "occurredAt",
      header: "Date",
      cell: (movement) => formatDateTime(movement.occurredAt, { locale, timeZone }),
    },
    {
      id: "type",
      header: "Mouvement",
      cell: (movement) => valuationMovementTypeLabel(movement.type),
    },
    {
      id: "direction",
      header: "Sens",
      cell: (movement) => valuationMovementDirection(movement.type),
    },
    {
      id: "quantity",
      header: "Quantité",
      cell: (movement) => formatQuantity(movement.quantity, { locale }),
    },
    {
      id: "unitCost",
      header: "Coût unitaire",
      cell: (movement) => formatMoney(movement.unitCost, { currency: movement.currency, locale }),
    },
    {
      id: "value",
      header: "Valeur du mouvement",
      cell: (movement) => formatMoney(movement.value, { currency: movement.currency, locale }),
    },
    {
      id: "previousTotalValue",
      header: "Valeur avant",
      cell: (movement) =>
        formatMoney(movement.previousTotalValue, { currency: movement.currency, locale }),
    },
    {
      id: "resultingTotalValue",
      header: "Valeur après",
      cell: (movement) =>
        formatMoney(movement.resultingTotalValue, { currency: movement.currency, locale }),
    },
    {
      id: "averageCost",
      header: "Coût moyen avant → après",
      cell: (movement) =>
        `${formatMoney(movement.previousAverageCost, { currency: movement.currency, locale })} → ${formatMoney(movement.resultingAverageCost, { currency: movement.currency, locale })}`,
    },
    {
      id: "source",
      header: "Source",
      cell: (movement) => valuationMovementSourceLabel(movement.sourceType),
    },
  ];
  const totalItems =
    page.nextCursor === undefined
      ? (pageNumber - 1) * pageSize + page.items.length
      : (pageNumber + 1) * pageSize;

  return (
    <AdminTable
      columns={columns}
      emptyTitle="Aucun mouvement de valorisation"
      emptyDescription="Aucun mouvement économique n’est enregistré."
      getRowId={(movement) => movement.id}
      isLoading={false}
      onPageChange={onPageChange}
      page={{ items: page.items, page: pageNumber, pageSize, totalItems }}
      query={{ filters: {}, page: pageNumber }}
    />
  );
}
