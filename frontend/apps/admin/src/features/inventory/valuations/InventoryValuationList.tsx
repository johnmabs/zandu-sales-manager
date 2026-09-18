"use client";

import { ApiRequestError } from "@zandu/api-client";
import { formatMoney, formatQuantity } from "@zandu/domain-formatting";
import { ErrorMapper } from "@zandu/error-contract";
import { Button, EmptyState, ErrorState, Spinner } from "@zandu/ui";

import { AdminTable } from "../../../components/tables/AdminTable";

import type { AdminTableColumn } from "../../../components/tables/AdminTable";
import type { InventoryValuationResource, StockResource } from "@zandu/api-client";

export type InventoryValuationRow = Readonly<{
  stock: StockResource;
  valuation?: InventoryValuationResource;
}>;

export function InventoryValuationList({
  currency,
  error,
  isLoading,
  locale,
  onRetry,
  rows,
}: Readonly<{
  currency: string;
  error?: unknown;
  isLoading: boolean;
  locale: string;
  onRetry: () => void;
  rows?: readonly InventoryValuationRow[];
}>) {
  if (isLoading) return <Spinner label="Chargement des valorisations" />;
  if (error !== null && error !== undefined)
    return <ValuationLoadError error={error} onRetry={onRetry} />;
  if (rows === undefined || rows.length === 0) {
    return (
      <EmptyState
        title="Aucune position à valoriser"
        description="Aucune position Stock n’est disponible dans le magasin actif."
      />
    );
  }

  const columns: readonly AdminTableColumn<InventoryValuationRow>[] = [
    {
      id: "productId",
      header: "Produit",
      cell: ({ stock }) => (
        <a href={`/admin/inventory/valuations?productId=${encodeURIComponent(stock.productId)}`}>
          {stock.productId}
        </a>
      ),
    },
    {
      id: "quantityOnHand",
      header: "Quantité valorisée",
      cell: ({ stock, valuation }) =>
        formatQuantity(valuation?.quantityOnHand ?? stock.quantityOnHand, { locale }),
    },
    {
      id: "status",
      header: "État",
      cell: ({ valuation }) => (valuation === undefined ? "Non initialisée" : "Initialisée"),
    },
    {
      id: "averageUnitCost",
      header: "Coût moyen",
      cell: ({ valuation }) =>
        valuation === undefined
          ? "—"
          : formatMoney(valuation.averageUnitCost, { currency, locale }),
    },
    {
      id: "totalValue",
      header: "Valeur totale",
      cell: ({ valuation }) =>
        valuation === undefined ? "—" : formatMoney(valuation.totalValue, { currency, locale }),
    },
    {
      id: "version",
      header: "Version",
      cell: ({ valuation }) => valuation?.version ?? "—",
    },
  ];

  return (
    <>
      <p>Devise du magasin : {currency}</p>
      <AdminTable
        columns={columns}
        emptyTitle="Aucune valorisation"
        emptyDescription="Aucune position ne correspond au magasin actif."
        getRowId={({ stock }) => stock.id}
        isLoading={false}
        page={{ items: rows, page: 1, pageSize: rows.length, totalItems: rows.length }}
        query={{ filters: {}, page: 1 }}
      />
    </>
  );
}

export function ValuationLoadError({
  error,
  onRetry,
}: Readonly<{ error: unknown; onRetry: () => void }>) {
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
