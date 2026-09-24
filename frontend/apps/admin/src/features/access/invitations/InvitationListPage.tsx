"use client";

import { useEffectiveAccess } from "@zandu/authorization";
import { useNotifications } from "@zandu/notifications/react";
import { useOrganizationContext } from "@zandu/organization-context";
import { ErrorState, Spinner } from "@zandu/ui";

import { useAdminRuntime } from "../../../runtime/AdminRuntime";
import { useCancelInvitation } from "../hooks/useCancelInvitation";
import { useInvitationList } from "../hooks/useInvitationList";

import { InvitationList } from "./InvitationList";

export function InvitationListPage() {
  const access = useEffectiveAccess();
  const { activeOrganizationId } = useOrganizationContext();
  const { api, queryClient } = useAdminRuntime();
  const notifications = useNotifications();
  const unresolved = access === undefined || activeOrganizationId === undefined;
  const allowed =
    !unresolved &&
    access.organizationId === activeOrganizationId &&
    access.permissions.includes("MEMBER_INVITE");
  const invitations = useInvitationList({
    access: allowed ? access : undefined,
    api: allowed ? api : undefined,
    organizationId: activeOrganizationId,
  });
  const cancellation = useCancelInvitation(
    allowed ? api : undefined,
    allowed
      ? {
          authorizationVersion: access.authorizationVersion,
          organizationId: activeOrganizationId,
          queryClient,
        }
      : undefined,
  );

  if (unresolved) return <Spinner label="Chargement des autorisations" />;
  if (!allowed) {
    return (
      <ErrorState
        description="Votre accès ne permet pas de consulter les invitations de l’organisation active."
        title="Accès refusé"
      />
    );
  }

  return (
    <InvitationList
      {...(cancellation.error === null ? {} : { cancellationError: cancellation.error })}
      {...(invitations.error === null ? {} : { error: invitations.error })}
      {...(invitations.data === undefined ? {} : { invitations: invitations.data })}
      isCancelling={cancellation.isPending}
      isLoading={invitations.isLoading}
      onCancel={async (invitation) => {
        await cancellation.mutateAsync(invitation.id);
        notifications.notify({ message: "Invitation annulée.", tone: "success" });
      }}
      onCancelStart={() => cancellation.reset()}
      onRetry={() => void invitations.refetch()}
    />
  );
}
