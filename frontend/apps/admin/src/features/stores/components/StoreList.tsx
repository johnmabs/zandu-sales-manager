"use client";

import { EmptyState, Skeleton } from "@zandu/ui";
import Link from "next/link";

import { AdminTable } from "../../../components/tables/AdminTable";

import { StoreErrorState, storeErrorPresentation } from "./StoreErrorState";
import { StoreStatusBadge } from "./StoreStatusBadge";

import type { AdminTableColumn } from "../../../components/tables/AdminTable";
import type { StoreResource } from "@zandu/api-client";

type StoreListProperties = Readonly<{
  canCreate: boolean;
  error?: unknown | undefined;
  isLoading: boolean;
  stores?: readonly StoreResource[] | undefined;
}>;

const columns: readonly AdminTableColumn<StoreResource>[] = [
  {
    cell: (store) => (
      <Link href={`/admin/stores/${encodeURIComponent(store.id)}`}>{store.name}</Link>
    ),
    header: "Nom",
    id: "name",
  },
  { cell: (store) => store.code, header: "Code", id: "code" },
  {
    cell: (store) => <StoreStatusBadge status={store.status} />,
    header: "Statut",
    id: "status",
  },
];

const tableQuery = { filters: {}, page: 1 } as const;

export function StoreList({
  canCreate,
  error,
  isLoading,
  onRetry,
  stores,
}: StoreListProperties & Readonly<{ onRetry?: () => void }>) {
  if (isLoading) {
    return <StoreListSkeleton />;
  }

  if (error != null) {
    return (
      <StoreErrorState error={error} {...(onRetry === undefined ? {} : { onRetry })} scope="list" />
    );
  }

  if (stores === undefined || stores.length === 0) {
    return (
      <EmptyState
        {...(canCreate
          ? {
              action: <Link href="/admin/stores/new">Créer un magasin</Link>,
            }
          : {})}
        description="Les magasins permettent de séparer les opérations et le stock de chaque point de vente."
        title="Aucun magasin accessible"
      />
    );
  }

  return (
    <AdminTable
      columns={columns}
      emptyDescription="Aucun magasin accessible."
      emptyTitle="Aucun magasin"
      getRowId={(store) => store.id}
      isLoading={false}
      page={{ items: stores, page: 1, pageSize: stores.length, totalItems: stores.length }}
      query={tableQuery}
    />
  );
}

export function StoreListSkeleton() {
  return (
    <div aria-label="Chargement des magasins" aria-busy="true" role="status">
      <Skeleton className="zandu-skeleton--title" />
      <Skeleton className="zandu-skeleton--table" />
    </div>
  );
}

export function storeListErrorPresentation(error: unknown): Readonly<{
  description: string;
  title: string;
}> {
  const presentation = storeErrorPresentation(error, "list");
  return { description: presentation.description, title: presentation.title };
}
