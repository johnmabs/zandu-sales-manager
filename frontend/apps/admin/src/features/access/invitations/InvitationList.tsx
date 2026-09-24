"use client";

import { Button, EmptyState, ErrorState, Skeleton } from "@zandu/ui";
import { useState } from "react";

import { AdminTable } from "../../../components/tables/AdminTable";

import { CancelInvitationDialog } from "./CancelInvitationDialog";

import type { AdminTableColumn } from "../../../components/tables/AdminTable";
import type { InvitationResource } from "@zandu/api-client";

const lifecycleColumns: readonly AdminTableColumn<InvitationResource>[] = [
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
  isCancelling = false,
  cancellationError,
  onCancel,
  onCancelStart,
  onRetry,
}: Readonly<{
  cancellationError?: unknown;
  error?: unknown;
  invitations?: readonly InvitationResource[];
  isCancelling?: boolean;
  isLoading: boolean;
  onCancel?: (invitation: InvitationResource) => Promise<void>;
  onCancelStart?: () => void;
  onRetry?: () => void;
}>) {
  const [selectedInvitation, setSelectedInvitation] = useState<InvitationResource>();
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

  const columns =
    onCancel === undefined
      ? lifecycleColumns
      : [
          ...lifecycleColumns,
          {
            cell: (invitation: InvitationResource) =>
              invitation.status === "PENDING" ? (
                <Button
                  aria-label={`Annuler l’invitation de ${invitation.email}`}
                  disabled={isCancelling}
                  onClick={() => {
                    onCancelStart?.();
                    setSelectedInvitation(invitation);
                  }}
                  type="button"
                  variant="danger"
                >
                  Annuler
                </Button>
              ) : null,
            header: "Actions",
            id: "actions",
          },
        ];

  return (
    <>
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
      <CancelInvitationDialog
        email={selectedInvitation?.email ?? ""}
        {...(cancellationError === undefined ? {} : { error: cancellationError })}
        isCancelling={isCancelling}
        onClose={() => setSelectedInvitation(undefined)}
        onConfirm={() => {
          if (selectedInvitation === undefined || onCancel === undefined) return;
          void onCancel(selectedInvitation)
            .then(() => setSelectedInvitation(undefined))
            .catch(() => undefined);
        }}
        open={selectedInvitation !== undefined}
      />
    </>
  );
}
