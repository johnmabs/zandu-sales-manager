"use client";

import { useEffectiveAccess } from "@zandu/authorization";
import { useOrganizationContext } from "@zandu/organization-context";
import { ErrorState, Spinner } from "@zandu/ui";

import { useAdminRuntime } from "../../../runtime/AdminRuntime";
import { useStoreList } from "../hooks/useStoreList";
import { resolveStoreAccess, storePermissions } from "../storeAuthorization";

import { StoreList } from "./StoreList";

import type { FoundationApi } from "@zandu/api-client";
import type { EffectiveAccess } from "@zandu/authorization";

export function StoreListPage() {
  const access = useEffectiveAccess();
  const { activeOrganizationId } = useOrganizationContext();
  const { api } = useAdminRuntime();

  return <StoreListWorkspace access={access} api={api} organizationId={activeOrganizationId} />;
}

export function StoreListWorkspace({
  access,
  api,
  organizationId,
}: Readonly<{
  access: EffectiveAccess | undefined;
  api: FoundationApi | undefined;
  organizationId: string | undefined;
}>) {
  const readAccess = resolveStoreAccess(access, organizationId, storePermissions.read);
  const canCreate =
    resolveStoreAccess(access, organizationId, storePermissions.create) === "ALLOWED";
  const stores = useStoreList({
    access: readAccess === "ALLOWED" ? access : undefined,
    api,
    organizationId,
  });

  if (readAccess === "UNRESOLVED") {
    return <Spinner label="Chargement des autorisations" />;
  }

  if (readAccess !== "ALLOWED") {
    return (
      <ErrorState
        description="Votre accès ne permet pas de consulter les magasins de l’organisation active."
        title="Accès refusé"
      />
    );
  }

  return (
    <section aria-labelledby="stores-page-title" className="zandu-store-list-page">
      <h1 id="stores-page-title">Magasins</h1>
      <StoreList
        canCreate={canCreate}
        error={stores.error}
        isLoading={stores.isLoading}
        onRetry={() => void stores.refetch()}
        stores={stores.data}
      />
    </section>
  );
}
