"use client";

import { useEffectiveAccess } from "@zandu/authorization";
import { useNotifications } from "@zandu/notifications/react";
import { useOrganizationContext } from "@zandu/organization-context";
import { Button, ErrorState, Spinner } from "@zandu/ui";
import { useRouter } from "next/navigation";

import { useAdminRuntime } from "../../../runtime/AdminRuntime";
import { useInviteMember } from "../hooks/useInviteMember";
import { useRoleCatalog } from "../hooks/useRoleCatalog";

import { InviteMemberForm } from "./InviteMemberForm";

import type { InvitationCreateInput } from "@zandu/api-client";

export function InviteMemberPage() {
  const access = useEffectiveAccess();
  const { activeOrganizationId } = useOrganizationContext();
  const { api } = useAdminRuntime();
  const notifications = useNotifications();
  const router = useRouter();
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
    await invite.mutateAsync(input);
    notifications.notify({ message: "Invitation créée.", tone: "success" });
    router.push("/app/access/invitations");
  };

  return <InviteMemberForm onInvite={onInvite} roles={activeRoles} />;
}
