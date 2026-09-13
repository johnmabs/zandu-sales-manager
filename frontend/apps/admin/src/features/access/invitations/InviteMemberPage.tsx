"use client";

import { useEffectiveAccess } from "@zandu/authorization";
import { useNotifications } from "@zandu/notifications/react";
import { useOrganizationContext } from "@zandu/organization-context";
import { ErrorState, Spinner } from "@zandu/ui";
import { useRouter } from "next/navigation";

import { useAdminRuntime } from "../../../runtime/AdminRuntime";
import { useInviteMember } from "../hooks/useInviteMember";

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

  if (unresolved) return <Spinner label="Chargement des autorisations" />;
  if (!allowed)
    return (
      <ErrorState
        description="Votre accès ne permet pas d’inviter un membre."
        title="Action non autorisée"
      />
    );

  const onInvite = async (input: InvitationCreateInput) => {
    await invite.mutateAsync(input);
    notifications.notify({ message: "Invitation créée.", tone: "success" });
    router.push("/app/access/invitations");
  };

  return <InviteMemberForm onInvite={onInvite} />;
}
