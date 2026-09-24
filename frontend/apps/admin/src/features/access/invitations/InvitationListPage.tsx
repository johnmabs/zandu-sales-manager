"use client";

import { useEffectiveAccess } from "@zandu/authorization";
import { useOrganizationContext } from "@zandu/organization-context";
import { ErrorState, Spinner } from "@zandu/ui";

import { useAdminRuntime } from "../../../runtime/AdminRuntime";
import { useInvitationList } from "../hooks/useInvitationList";

import { InvitationList } from "./InvitationList";

export function InvitationListPage() {
  const access = useEffectiveAccess();
  const { activeOrganizationId } = useOrganizationContext();
  const { api } = useAdminRuntime();
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
      {...(invitations.error === null ? {} : { error: invitations.error })}
      {...(invitations.data === undefined ? {} : { invitations: invitations.data })}
      isLoading={invitations.isLoading}
      onRetry={() => void invitations.refetch()}
    />
  );
}
