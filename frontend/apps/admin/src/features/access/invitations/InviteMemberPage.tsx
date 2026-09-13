"use client";

import { useEffectiveAccess } from "@zandu/authorization";
import { useNotifications } from "@zandu/notifications/react";
import { useOrganizationContext } from "@zandu/organization-context";
import { Button, ErrorState, Spinner } from "@zandu/ui";
import { useState } from "react";

import { useAdminRuntime } from "../../../runtime/AdminRuntime";
import { useStoreList } from "../../stores/hooks/useStoreList";
import { useInviteMember } from "../hooks/useInviteMember";
import { useRoleCatalog } from "../hooks/useRoleCatalog";

import { InvitationSuccessState } from "./InvitationSuccessState";
import { InviteMemberForm } from "./InviteMemberForm";

import type { CreatedInvitationResource, InvitationCreateInput } from "@zandu/api-client";

export function InviteMemberPage() {
  const access = useEffectiveAccess();
  const { activeOrganizationId } = useOrganizationContext();
  const { api } = useAdminRuntime();
  const notifications = useNotifications();
  const [createdInvitation, setCreatedInvitation] = useState<CreatedInvitationResource>();
  const invite = useInviteMember(api);
  const unresolved = access === undefined || activeOrganizationId === undefined;
  const allowed =
    !unresolved &&
    access.organizationId === activeOrganizationId &&
    access.permissions.includes("MEMBER_INVITE");
  const roleCatalog = useRoleCatalog({
    api: allowed ? api : undefined,
    authorizationVersion: allowed ? access?.authorizationVersion : undefined,
    organizationId: activeOrganizationId,
  });
  const stores = useStoreList({
    access: allowed ? access : undefined,
    api: allowed ? api : undefined,
    organizationId: activeOrganizationId,
  });

  if (unresolved) return <Spinner label="Chargement des autorisations" />;
  if (!allowed)
    return (
      <ErrorState
        description="Votre accès ne permet pas d’inviter un membre."
        title="Action non autorisée"
      />
    );

  if (roleCatalog.isLoading) return <Spinner label="Chargement des rôles" />;

  if (roleCatalog.error !== null) {
    return (
      <ErrorState
        action={
          <Button onClick={() => void roleCatalog.refetch()} type="button" variant="secondary">
            Réessayer
          </Button>
        }
        description="Les rôles disponibles n’ont pas pu être chargés."
        title="Impossible de préparer l’invitation"
      />
    );
  }

  const activeRoles = roleCatalog.data?.filter((role) => role.status === "ACTIVE") ?? [];

  if (activeRoles.length === 0) {
    return (
      <ErrorState
        description="Aucun rôle actif ne peut être attribué par invitation."
        title="Aucun rôle disponible"
      />
    );
  }

  const onInvite = async (input: InvitationCreateInput) => {
    const created = await invite.mutateAsync(input);
    setCreatedInvitation(created);
    notifications.notify({ message: "Invitation créée.", tone: "success" });
  };

  if (createdInvitation !== undefined) {
    return <InvitationSuccessState invitation={createdInvitation} />;
  }

  return (
    <InviteMemberForm
      onInvite={onInvite}
      roles={activeRoles}
      storeScope={{
        error: stores.error === null ? undefined : "unavailable",
        isLoading: stores.isLoading,
        onRetry: () => void stores.refetch(),
        stores: stores.data,
      }}
    />
  );
}
