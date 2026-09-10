"use client";

import { useEffectiveAccess } from "@zandu/authorization";
import { useNotifications } from "@zandu/notifications/react";
import { useOrganizationContext } from "@zandu/organization-context";
import { ErrorState, Spinner } from "@zandu/ui";
import { useRouter } from "next/navigation";

import { useAdminRuntime } from "../../../runtime/AdminRuntime";
import { useStoreDetails } from "../hooks/useStoreDetails";
import { useUpdateStore } from "../hooks/useUpdateStore";
import { useSingleFlight } from "../mutationSafety";
import { resolveStoreAccess, storePermissions } from "../storeAuthorization";

import { storeDetailsErrorPresentation } from "./StoreDetails";
import { StoreUpdateForm } from "./StoreUpdateForm";

import type { StoreUpdateInput } from "@zandu/api-client";

export function EditStorePage({ storeId }: Readonly<{ storeId: string }>) {
  const access = useEffectiveAccess();
  const { activeOrganizationId } = useOrganizationContext();
  const { api, queryClient, refreshStoreContext } = useAdminRuntime();
  const notifications = useNotifications();
  const router = useRouter();
  const runSingleFlight = useSingleFlight();
  const editAccess = resolveStoreAccess(
    access,
    activeOrganizationId,
    storePermissions.update,
    storeId,
  );
  const details = useStoreDetails({
    access: editAccess === "ALLOWED" ? access : undefined,
    api,
    organizationId: activeOrganizationId,
    storeId,
  });
  const update = useUpdateStore({
    api,
    authorizationVersion: access?.authorizationVersion ?? 0,
    organizationId: activeOrganizationId ?? "unresolved-organization",
    queryClient,
    storeId,
  });

  if (editAccess === "UNRESOLVED") {
    return <Spinner label="Chargement des autorisations" />;
  }

  if (editAccess === "OUT_OF_SCOPE") {
    return (
      <ErrorState
        description="Ce magasin est introuvable ou n’est pas accessible dans l’organisation active."
        title="Magasin introuvable"
      />
    );
  }

  if (editAccess === "DENIED" || activeOrganizationId === undefined) {
    return (
      <ErrorState
        description="Votre accès ne permet pas de modifier ce magasin dans l’organisation active."
        title="Action non autorisée"
      />
    );
  }

  if (details.isLoading) {
    return <Spinner label="Chargement du magasin" />;
  }

  if (details.error !== null) {
    return <ErrorState {...storeDetailsErrorPresentation(details.error)} />;
  }

  if (details.data === undefined) {
    return (
      <ErrorState
        description="Ce magasin est introuvable ou n’est pas accessible dans l’organisation active."
        title="Magasin introuvable"
      />
    );
  }

  const updateStore = async (input: StoreUpdateInput) => {
    const result = await runSingleFlight(async () => {
      await update.mutateAsync(input);
      await refreshStoreContext();
      return true;
    });
    if (result === undefined) {
      return;
    }
    notifications.notify({ message: "Magasin mis à jour.", tone: "success" });
    router.push(`/app/stores/${encodeURIComponent(storeId)}`);
  };

  return (
    <section aria-labelledby="edit-store-title">
      <h1 id="edit-store-title">Modifier le magasin</h1>
      <StoreUpdateForm
        key={`${details.data.id}:${details.data.version}`}
        onConflictReload={() => void details.refetch()}
        onUpdate={updateStore}
        store={details.data}
      />
    </section>
  );
}
