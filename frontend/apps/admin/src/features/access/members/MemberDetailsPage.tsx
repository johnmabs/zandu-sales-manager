"use client";

import { useEffectiveAccess } from "@zandu/authorization";
import { useOrganizationContext } from "@zandu/organization-context";
import { ErrorState, Spinner } from "@zandu/ui";

import { useAdminRuntime } from "../../../runtime/AdminRuntime";
import { useMemberDetails } from "../hooks/useMemberDetails";

import { resolveMemberAccess } from "./memberAuthorization";
import { MemberDetails } from "./MemberDetails";

export function MemberDetailsPage({ membershipId }: Readonly<{ membershipId: string }>) {
  const access = useEffectiveAccess();
  const { activeOrganizationId } = useOrganizationContext();
  const { api } = useAdminRuntime();
  const readAccess = resolveMemberAccess(access, activeOrganizationId);
  const details = useMemberDetails({
    access: readAccess === "ALLOWED" ? access : undefined,
    api,
    membershipId,
    organizationId: activeOrganizationId,
  });

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
    <MemberDetails
      {...(details.error === null ? {} : { error: details.error })}
      isLoading={details.isLoading}
      {...(details.data === undefined ? {} : { member: details.data })}
      onRetry={() => void details.refetch()}
    />
  );
}
