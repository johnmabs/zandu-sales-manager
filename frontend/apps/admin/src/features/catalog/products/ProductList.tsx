"use client";
import { ApiRequestError } from "@zandu/api-client";
import { ErrorMapper } from "@zandu/error-contract";
import { Button, EmptyState, ErrorState, Spinner } from "@zandu/ui";

import { AdminTable } from "../../../components/tables/AdminTable";

import type { AdminTableColumn } from "../../../components/tables/AdminTable";
import type { ProductResource } from "@zandu/api-client";

const columns: readonly AdminTableColumn<ProductResource>[] = [
  { id: "productCode", header: "Code", cell: (p) => p.productCode },
  {
    id: "name",
    header: "Nom",
    cell: (p) => <a href={`/app/catalog/products/${p.id}`}>{p.name}</a>,
  },
  { id: "status", header: "Statut", cell: (p) => p.status },
  { id: "type", header: "Type", cell: (p) => p.type },
  { id: "categoryId", header: "Catégorie", cell: (p) => p.categoryId ?? "Sans catégorie" },
];
export function ProductList({
  products,
  isLoading,
  error,
  onRetry,
}: Readonly<{
  products?: readonly ProductResource[] | undefined;
  isLoading: boolean;
  error?: unknown;
  onRetry: () => void;
}>) {
  if (isLoading) return <Spinner label="Chargement des produits" />;
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
  if (products === undefined || products.length === 0)
    return (
      <EmptyState
        title="Aucun produit"
        description="Aucun produit ne correspond à cette recherche. Modifiez les filtres ou réessayez après l’ajout de produits au catalogue."
      />
    );
  return (
    <AdminTable
      columns={columns}
      emptyTitle="Aucun produit"
      emptyDescription="Aucun produit ne correspond à cette recherche."
      getRowId={(p) => p.id}
      isLoading={false}
      page={{ items: products, page: 1, pageSize: products.length, totalItems: products.length }}
      query={{ filters: {}, page: 1 }}
    />
  );
}
