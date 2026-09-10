"use client";

import { useEffectiveAccess } from "@zandu/authorization";
import { useOrganizationContext } from "@zandu/organization-context";
import { ErrorState, Spinner } from "@zandu/ui";

import { useAdminRuntime } from "../../../runtime/AdminRuntime";
import { useStoreList } from "../hooks/useStoreList";
import { resolveStoreAccess, storePermissions } from "../storeAuthorization";

import { StoreList } from "./StoreList";

export function StoreListPage() {
  const access = useEffectiveAccess();
  const { activeOrganizationId } = useOrganizationContext();
  const { api } = useAdminRuntime();
  const readAccess = resolveStoreAccess(access, activeOrganizationId, storePermissions.read);
  const canCreate =
    resolveStoreAccess(access, activeOrganizationId, storePermissions.create) === "ALLOWED";
  const stores = useStoreList({
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
