"use client";

import { useEffectiveAccess } from "@zandu/authorization";
import { useOrganizationContext } from "@zandu/organization-context";
import { ErrorState, Spinner } from "@zandu/ui";

import { useAdminRuntime } from "../../../runtime/AdminRuntime";
import { useMemberList } from "../hooks/useMemberList";

import { resolveMemberAccess } from "./memberAuthorization";
import { MemberList } from "./MemberList";

export function MemberListPage() {
  const access = useEffectiveAccess();
  const { activeOrganizationId } = useOrganizationContext();
  const { api } = useAdminRuntime();
  const readAccess = resolveMemberAccess(access, activeOrganizationId);
  const members = useMemberList({
    access: readAccess === "ALLOWED" ? access : undefined,
    api,
    organizationId: activeOrganizationId,
  });

  if (readAccess === "UNRESOLVED") {
    return <Spinner label="Chargement des autorisations" />;
  }

  if (readAccess !== "ALLOWED") {
    return (
      <ErrorState
        description="Votre accès ne permet pas de consulter les membres de l’organisation active."
        title="Accès refusé"
      />
    );
  }

  return (
    <div className="zandu-member-list-page">
      <MemberList
        {...(members.error === null ? {} : { error: members.error })}
        isLoading={members.isLoading}
        {...(members.data === undefined ? {} : { members: members.data })}
        onRetry={() => void members.refetch()}
      />
    </div>
  );
}
