"use client";

import { Button, EmptyState, ErrorState, Skeleton } from "@zandu/ui";

import { AdminTable } from "../../../components/tables/AdminTable";

import type { AdminTableColumn } from "../../../components/tables/AdminTable";
import type { InvitationResource } from "@zandu/api-client";

const columns: readonly AdminTableColumn<InvitationResource>[] = [
  { cell: (invitation) => invitation.email, header: "Email", id: "email" },
  { cell: (invitation) => invitation.status, header: "Statut", id: "status" },
  {
    cell: (invitation) => invitation.roleAssignments.map(({ roleCode }) => roleCode).join(", "),
    header: "Rôles",
    id: "roles",
  },
  {
    cell: (invitation) =>
      new Intl.DateTimeFormat("fr", { dateStyle: "medium" }).format(new Date(invitation.expiresAt)),
    header: "Expiration",
    id: "expiresAt",
  },
];

const tableQuery = { filters: {}, page: 1 } as const;

export function InvitationList({
  error,
  invitations,
  isLoading,
  onRetry,
}: Readonly<{
  error?: unknown;
  invitations?: readonly InvitationResource[];
  isLoading: boolean;
  onRetry?: () => void;
}>) {
  if (isLoading) {
    return (
      <div aria-busy="true" aria-label="Chargement des invitations" role="status">
        <Skeleton className="zandu-skeleton--title" />
        <Skeleton className="zandu-skeleton--table" />
      </div>
    );
  }
  if (error !== undefined) {
    return (
      <ErrorState
        action={
          onRetry === undefined ? undefined : (
            <Button onClick={onRetry} type="button" variant="secondary">
              Réessayer
            </Button>
          )
        }
        description="La liste des invitations n’a pas pu être chargée."
        title="Impossible de charger les invitations"
      />
    );
  }
  if (invitations === undefined || invitations.length === 0) {
    return (
      <EmptyState
        description="Les invitations de cette organisation apparaîtront ici."
        title="Aucune invitation"
      />
    );
  }

  return (
    <AdminTable
      columns={columns}
      emptyDescription="Les invitations de cette organisation apparaîtront ici."
      emptyTitle="Aucune invitation"
      getRowId={(invitation) => invitation.id}
      isLoading={false}
      page={{
        items: invitations,
        page: 1,
        pageSize: invitations.length,
        totalItems: invitations.length,
      }}
      query={tableQuery}
    />
  );
}
