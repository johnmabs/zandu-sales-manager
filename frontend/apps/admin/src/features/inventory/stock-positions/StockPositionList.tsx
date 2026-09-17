"use client";

import { ApiRequestError } from "@zandu/api-client";
import { formatQuantity } from "@zandu/domain-formatting";
import { ErrorMapper } from "@zandu/error-contract";
import { Button, EmptyState, ErrorState, Spinner } from "@zandu/ui";

import { AdminTable } from "../../../components/tables/AdminTable";

import type { AdminTableColumn } from "../../../components/tables/AdminTable";
import type { ProductResource, StockResource } from "@zandu/api-client";

export type StockPositionRow = Readonly<{
  product?: ProductResource;
  stock: StockResource;
}>;

export function StockPositionList({
  error,
  isLoading,
  locale,
  onRetry,
  rows,
}: Readonly<{
  error?: unknown;
  isLoading: boolean;
  locale: string;
  onRetry: () => void;
  rows?: readonly StockPositionRow[];
}>) {
  if (isLoading) return <Spinner label="Chargement des positions de stock" />;

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

  if (rows === undefined || rows.length === 0) {
    return (
      <EmptyState
        title="Aucune position de stock"
        description="Aucune position ne correspond à cette recherche pour le magasin actif."
      />
    );
  }

  const columns: readonly AdminTableColumn<StockPositionRow>[] = [
    {
      id: "productCode",
      header: "Code produit",
      cell: ({ product, stock }) => product?.productCode ?? stock.productId,
    },
    {
      id: "productName",
      header: "Produit",
      cell: ({ product, stock }) => (
        <a href={`/admin/inventory/positions/${stock.productId}`}>
          {product?.name ?? "Métadonnées indisponibles"}
        </a>
      ),
    },
    {
      id: "quantityOnHand",
      header: "Quantité disponible",
      cell: ({ stock }) => formatQuantity(stock.quantityOnHand, { locale }),
    },
    {
      id: "initialized",
      header: "État",
      cell: ({ stock }) => (stock.initialized ? "Initialisé" : "À initialiser"),
    },
  ];

  return (
    <AdminTable
      columns={columns}
      emptyTitle="Aucune position de stock"
      emptyDescription="Aucune position ne correspond à cette recherche."
      getRowId={({ stock }) => stock.id}
      isLoading={false}
      page={{ items: rows, page: 1, pageSize: rows.length, totalItems: rows.length }}
      query={{ filters: {}, page: 1 }}
    />
  );
}
