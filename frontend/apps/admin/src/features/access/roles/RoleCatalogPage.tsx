"use client";

import { useEffectiveAccess } from "@zandu/authorization";
import { useOrganizationContext } from "@zandu/organization-context";
import { ErrorState, Spinner } from "@zandu/ui";

import { useAdminRuntime } from "../../../runtime/AdminRuntime";
import { useRoleCatalog } from "../hooks/useRoleCatalog";

import { resolveRoleReadAccess } from "./roleAuthorization";
import { RoleCatalog } from "./RoleCatalog";

export function RoleCatalogPage() {
  const access = useEffectiveAccess();
  const { activeOrganizationId } = useOrganizationContext();
  const { api } = useAdminRuntime();
  const readAccess = resolveRoleReadAccess(access, activeOrganizationId);
  const roles = useRoleCatalog({
    api: readAccess === "ALLOWED" ? api : undefined,
    authorizationVersion: readAccess === "ALLOWED" ? access?.authorizationVersion : undefined,
    organizationId: activeOrganizationId,
  });

  if (readAccess === "UNRESOLVED") {
    return <Spinner label="Chargement des autorisations" />;
  }

  if (readAccess !== "ALLOWED") {
    return (
      <ErrorState
        description="Votre accès ne permet pas de consulter les rôles de l’organisation active."
        title="Accès refusé"
      />
    );
  }

  return (
    <div className="zandu-role-catalog-page">
      <RoleCatalog
        {...(roles.error === null ? {} : { error: roles.error })}
        isLoading={roles.isLoading}
        {...(roles.data === undefined ? {} : { roles: roles.data })}
        onRetry={() => void roles.refetch()}
      />
    </div>
  );
}
