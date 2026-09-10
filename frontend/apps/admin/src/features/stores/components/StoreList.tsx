"use client";

import { ApiRequestError } from "@zandu/api-client";
import { Badge, EmptyState, ErrorState, Spinner } from "@zandu/ui";
import Link from "next/link";

import { AdminTable } from "../../../components/tables/AdminTable";

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
    cell: (store) => <Link href={`/app/stores/${encodeURIComponent(store.id)}`}>{store.name}</Link>,
    header: "Nom",
    id: "name",
  },
  { cell: (store) => store.code, header: "Code", id: "code" },
  {
    cell: (store) => <StoreStatus status={store.status} />,
    header: "Statut",
    id: "status",
  },
];

const tableQuery = { filters: {}, page: 1 } as const;

export function StoreList({ canCreate, error, isLoading, stores }: StoreListProperties) {
  if (isLoading) {
    return <Spinner label="Chargement des magasins" />;
  }

  if (error !== undefined) {
    const presentation = storeListErrorPresentation(error);

    return <ErrorState {...presentation} />;
  }

  if (stores === undefined || stores.length === 0) {
    return (
      <EmptyState
        {...(canCreate
          ? {
              action: <Link href="/app/stores/new">Créer un magasin</Link>,
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

export function storeListErrorPresentation(error: unknown): Readonly<{
  description: string;
  title: string;
}> {
  if (error instanceof ApiRequestError) {
    if (error.apiError.kind === "response" && error.apiError.status === 403) {
      return {
        description: "Votre accès ne permet pas de consulter les magasins de cette organisation.",
        title: "Accès refusé",
      };
    }

    if (error.apiError.kind === "network") {
      return {
        description: "Vérifiez votre connexion puis réessayez.",
        title: "Connexion indisponible",
      };
    }
  }

  return {
    description: "Le serveur n’a pas pu fournir la liste des magasins. Réessayez ultérieurement.",
    title: "Impossible de charger les magasins",
  };
}

function StoreStatus({ status }: Pick<StoreResource, "status">) {
  const tone =
    status === "ACTIVE"
      ? "success"
      : status === "SUSPENDED" || status === "CLOSURE_PENDING"
        ? "warning"
        : "neutral";

  return <Badge tone={tone}>{status}</Badge>;
}
