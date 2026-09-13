"use client";

import { Button, EmptyState, ErrorState, Skeleton } from "@zandu/ui";

import { AdminTable } from "../../../components/tables/AdminTable";

import type { AdminTableColumn } from "../../../components/tables/AdminTable";
import type { RoleResource } from "@zandu/api-client";

type RoleCatalogProperties = Readonly<{
  error?: unknown | undefined;
  isLoading: boolean;
  onRetry?: (() => void) | undefined;
  roles?: readonly RoleResource[] | undefined;
}>;

const columns: readonly AdminTableColumn<RoleResource>[] = [
  { cell: (role) => role.name, header: "Rôle", id: "name" },
  { cell: (role) => role.code, header: "Code", id: "code" },
  {
    cell: (role) => role.description ?? "Aucune description",
    header: "Description",
    id: "description",
  },
  {
    cell: (role) => (
      <ul className="zandu-role-catalog__permissions">
        {role.permissions.map((permission) => (
          <li key={permission}>{permission}</li>
        ))}
      </ul>
    ),
    header: "Permissions",
    id: "permissions",
  },
  { cell: (role) => role.status, header: "Statut", id: "status" },
  {
    cell: (role) => (role.type === "SYSTEM" ? "Système (lecture seule)" : "Personnalisé"),
    header: "Type",
    id: "type",
  },
];

const tableQuery = { filters: {}, page: 1 } as const;

export function RoleCatalog({ error, isLoading, onRetry, roles }: RoleCatalogProperties) {
  if (isLoading) {
    return <RoleCatalogSkeleton />;
  }

  if (error !== undefined) {
    return <RoleCatalogError {...(onRetry === undefined ? {} : { onRetry })} />;
  }

  if (roles === undefined || roles.length === 0) {
    return <EmptyState description="Les rôles publiés apparaîtront ici." title="Aucun rôle" />;
  }

  return (
    <AdminTable
      columns={columns}
      emptyDescription="Les rôles publiés apparaîtront ici."
      emptyTitle="Aucun rôle"
      getRowId={(role) => role.id}
      isLoading={false}
      page={{ items: roles, page: 1, pageSize: roles.length, totalItems: roles.length }}
      query={tableQuery}
    />
  );
}

export function RoleCatalogSkeleton() {
  return (
    <div aria-label="Chargement des rôles" aria-busy="true" role="status">
      <Skeleton className="zandu-skeleton--title" />
      <Skeleton className="zandu-skeleton--table" />
    </div>
  );
}

function RoleCatalogError({ onRetry }: Readonly<{ onRetry?: (() => void) | undefined }>) {
  return (
    <ErrorState
      action={
        onRetry === undefined ? undefined : (
          <Button onClick={onRetry} type="button" variant="secondary">
            Réessayer
          </Button>
        )
      }
      description="Le catalogue des rôles n’a pas pu être chargé."
      title="Impossible de charger les rôles"
    />
  );
}
