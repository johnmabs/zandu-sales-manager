"use client";

import { useEffectiveAccess } from "@zandu/authorization";
import { useNotifications } from "@zandu/notifications/react";
import { useOrganizationContext } from "@zandu/organization-context";
import { ErrorState, Spinner } from "@zandu/ui";
import { useState } from "react";

import { useAdminRuntime } from "../../../runtime/AdminRuntime";
import { useStoreList } from "../../stores/hooks/useStoreList";
import { AccessMutationError } from "../accessErrors";
import { useAssignMemberRole } from "../hooks/useAssignMemberRole";
import { useMemberDetails } from "../hooks/useMemberDetails";
import { useReactivateMember } from "../hooks/useReactivateMember";
import { useRemoveMemberRole } from "../hooks/useRemoveMemberRole";
import { useRevokeMember } from "../hooks/useRevokeMember";
import { useRoleCatalog } from "../hooks/useRoleCatalog";
import { useSuspendMember } from "../hooks/useSuspendMember";

import { AssignRoleDialog } from "./AssignRoleDialog";
import { availableMemberActions, resolveMemberAccess } from "./memberAuthorization";
import { MemberDetails } from "./MemberDetails";
import { ReactivateMemberDialog } from "./ReactivateMemberDialog";
import { RemoveRoleDialog } from "./RemoveRoleDialog";
import { RevokeMemberDialog } from "./RevokeMemberDialog";
import { SuspendMemberDialog } from "./SuspendMemberDialog";

import type { MembershipRoleAssignment } from "@zandu/api-client";

export function MemberDetailsPage({ membershipId }: Readonly<{ membershipId: string }>) {
  const access = useEffectiveAccess();
  const { activeOrganizationId } = useOrganizationContext();
  const { api, authState, revalidateSession } = useAdminRuntime();
  const notifications = useNotifications();
  const [assignOpen, setAssignOpen] = useState(false);
  const [assignmentToRemove, setAssignmentToRemove] = useState<MembershipRoleAssignment>();
  const [suspendOpen, setSuspendOpen] = useState(false);
  const [reactivateOpen, setReactivateOpen] = useState(false);
  const [revokeOpen, setRevokeOpen] = useState(false);
  const readAccess = resolveMemberAccess(access, activeOrganizationId);
  const details = useMemberDetails({
    access: readAccess === "ALLOWED" ? access : undefined,
    api,
    membershipId,
    organizationId: activeOrganizationId,
  });
  const actions = availableMemberActions(
    details.data?.status ?? "UNKNOWN",
    access?.permissions ?? [],
  );
  const canAssign = readAccess === "ALLOWED" && actions.assignRole;
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
  const remove = useRemoveMemberRole(api, membershipId);
  const suspend = useSuspendMember(api, membershipId);
  const reactivate = useReactivateMember(api, membershipId);
  const revoke = useRevokeMember(api, membershipId);
  const refreshAfterAccessMutation = async () => {
    if (details.data?.userId === authState.actor?.userId) {
      await revalidateSession();
      return;
    }
    await details.refetch();
  };
  const mutationError =
    assign.error ?? remove.error ?? suspend.error ?? reactivate.error ?? revoke.error;

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
      {mutationError === null || mutationError === undefined ? null : (
        <AccessMutationError error={mutationError} />
      )}
      <MemberDetails
        {...(details.error === null ? {} : { error: details.error })}
        isLoading={details.isLoading}
        {...(details.data === undefined ? {} : { member: details.data })}
        onRetry={() => void details.refetch()}
        onAssignRole={canAssign ? () => setAssignOpen(true) : undefined}
        onRemoveRole={actions.removeRole ? setAssignmentToRemove : undefined}
        onSuspend={actions.suspend ? () => setSuspendOpen(true) : undefined}
        onReactivate={actions.reactivate ? () => setReactivateOpen(true) : undefined}
        onRevoke={actions.revoke ? () => setRevokeOpen(true) : undefined}
      />
      <RevokeMemberDialog
        isPending={revoke.isPending}
        memberLabel={details.data?.userId ?? "ce membre"}
        onClose={() => setRevokeOpen(false)}
        onConfirm={async () => {
          await revoke.mutateAsync();
          await refreshAfterAccessMutation();
          notifications.notify({ message: "Membre révoqué.", tone: "success" });
          setRevokeOpen(false);
        }}
        open={revokeOpen}
      />
      <ReactivateMemberDialog
        isPending={reactivate.isPending}
        memberLabel={details.data?.userId ?? "ce membre"}
        onClose={() => setReactivateOpen(false)}
        onConfirm={async () => {
          await reactivate.mutateAsync();
          await refreshAfterAccessMutation();
          notifications.notify({ message: "Membre réactivé.", tone: "success" });
          setReactivateOpen(false);
        }}
        open={reactivateOpen}
      />
      <SuspendMemberDialog
        isPending={suspend.isPending}
        memberLabel={details.data?.userId ?? "ce membre"}
        onClose={() => setSuspendOpen(false)}
        onConfirm={async () => {
          await suspend.mutateAsync();
          await refreshAfterAccessMutation();
          notifications.notify({ message: "Membre suspendu.", tone: "success" });
          setSuspendOpen(false);
        }}
        open={suspendOpen}
      />
      <RemoveRoleDialog
        {...(assignmentToRemove === undefined ? {} : { assignment: assignmentToRemove })}
        isRemoving={remove.isPending}
        isOwner={
          roles.data?.some(
            (role) => role.id === assignmentToRemove?.roleId && role.code === "ORGANIZATION_OWNER",
          ) ?? false
        }
        onClose={() => setAssignmentToRemove(undefined)}
        onConfirm={async () => {
          if (assignmentToRemove === undefined) return;
          await remove.mutateAsync(assignmentToRemove.assignmentId);
          await refreshAfterAccessMutation();
          notifications.notify({ message: "Rôle retiré.", tone: "success" });
          setAssignmentToRemove(undefined);
        }}
        open={assignmentToRemove !== undefined}
      />
      <AssignRoleDialog
        isAssigning={assign.isPending}
        onClose={() => setAssignOpen(false)}
        onConfirm={async (input) => {
          await assign.mutateAsync(input);
          await refreshAfterAccessMutation();
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
