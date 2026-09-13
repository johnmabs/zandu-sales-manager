"use client";

import { useEffectiveAccess } from "@zandu/authorization";
import { useNotifications } from "@zandu/notifications/react";
import { useOrganizationContext } from "@zandu/organization-context";
import { ErrorState, Spinner } from "@zandu/ui";
import { useState } from "react";

import { useAdminRuntime } from "../../../runtime/AdminRuntime";
import { useStoreList } from "../../stores/hooks/useStoreList";
import { useAssignMemberRole } from "../hooks/useAssignMemberRole";
import { useMemberDetails } from "../hooks/useMemberDetails";
import { useRoleCatalog } from "../hooks/useRoleCatalog";

import { AssignRoleDialog } from "./AssignRoleDialog";
import { resolveMemberAccess } from "./memberAuthorization";
import { MemberDetails } from "./MemberDetails";

export function MemberDetailsPage({ membershipId }: Readonly<{ membershipId: string }>) {
  const access = useEffectiveAccess();
  const { activeOrganizationId } = useOrganizationContext();
  const { api } = useAdminRuntime();
  const notifications = useNotifications();
  const [assignOpen, setAssignOpen] = useState(false);
  const readAccess = resolveMemberAccess(access, activeOrganizationId);
  const details = useMemberDetails({
    access: readAccess === "ALLOWED" ? access : undefined,
    api,
    membershipId,
    organizationId: activeOrganizationId,
  });
  const canAssign =
    readAccess === "ALLOWED" && access?.permissions.includes("ROLE_ASSIGN") === true;
  const roles = useRoleCatalog({
    api: canAssign ? api : undefined,
    authorizationVersion: canAssign ? access?.authorizationVersion : undefined,
    organizationId: activeOrganizationId,
  });
  const stores = useStoreList({
    access: canAssign ? access : undefined,
    api: canAssign ? api : undefined,
    organizationId: activeOrganizationId,
  });
  const assign = useAssignMemberRole(api, membershipId);

  if (readAccess === "UNRESOLVED") {
    return <Spinner label="Chargement des autorisations" />;
  }

  if (readAccess === "OUT_OF_SCOPE") {
    return (
      <ErrorState
        description="Ce membre est introuvable ou n’est pas accessible dans l’organisation active."
        title="Membre introuvable"
      />
    );
  }

  if (readAccess === "DENIED") {
    return (
      <ErrorState
        description="Votre accès ne permet pas de consulter ce membre."
        title="Accès refusé"
      />
    );
  }

  return (
    <>
      <MemberDetails
        {...(details.error === null ? {} : { error: details.error })}
        isLoading={details.isLoading}
        {...(details.data === undefined ? {} : { member: details.data })}
        onRetry={() => void details.refetch()}
        onAssignRole={canAssign ? () => setAssignOpen(true) : undefined}
      />
      <AssignRoleDialog
        isAssigning={assign.isPending}
        onClose={() => setAssignOpen(false)}
        onConfirm={async (input) => {
          await assign.mutateAsync(input);
          await details.refetch();
          notifications.notify({ message: "Rôle attribué.", tone: "success" });
          setAssignOpen(false);
        }}
        open={assignOpen}
        roles={roles.data ?? []}
        stores={stores.data ?? []}
      />
    </>
  );
}
