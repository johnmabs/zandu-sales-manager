"use client";

import { useEffectiveAccess } from "@zandu/authorization";
import { useNotifications } from "@zandu/notifications/react";
import { useOrganizationContext } from "@zandu/organization-context";
import { ErrorState, Spinner } from "@zandu/ui";
import { useRouter } from "next/navigation";

import { useAdminRuntime } from "../../../runtime/AdminRuntime";
import { useCreateStore } from "../hooks/useCreateStore";
import { useSingleFlight } from "../mutationSafety";
import { resolveStoreAccess, storePermissions } from "../storeAuthorization";

import { StoreCreateForm } from "./StoreCreateForm";

import type { QueryClient } from "@tanstack/react-query";
import type { FoundationApi, StoreCreateInput } from "@zandu/api-client";
import type { EffectiveAccess } from "@zandu/authorization";
import type { Organization } from "@zandu/organization-context";

export function CreateStorePage() {
  const access = useEffectiveAccess();
  const { activeOrganization } = useOrganizationContext();
  const { api, queryClient, refreshStoreContext } = useAdminRuntime();
  const notifications = useNotifications();
  const router = useRouter();

  return (
    <CreateStoreWorkspace
      access={access}
      activeOrganization={activeOrganization}
      api={api}
      navigateToStore={(storeId) => router.push(`/admin/stores/${encodeURIComponent(storeId)}`)}
      notifyCreated={() => notifications.notify({ message: "Magasin créé.", tone: "success" })}
      queryClient={queryClient}
      refreshStoreContext={refreshStoreContext}
    />
  );
}

export function CreateStoreWorkspace({
  access,
  activeOrganization,
  api,
  navigateToStore,
  notifyCreated,
  queryClient,
  refreshStoreContext,
}: Readonly<{
  access: EffectiveAccess | undefined;
  activeOrganization: Organization | undefined;
  api: FoundationApi | undefined;
  navigateToStore: (storeId: string) => void;
  notifyCreated: () => void;
  queryClient: QueryClient;
  refreshStoreContext: () => Promise<void>;
}>) {
  const runSingleFlight = useSingleFlight();
  const createAccess = resolveStoreAccess(access, activeOrganization?.id, storePermissions.create);
  const create = useCreateStore({
    api,
    authorizationVersion: access?.authorizationVersion ?? 0,
    organizationId: activeOrganization?.id ?? "unresolved-organization",
    queryClient,
  });

  if (createAccess === "UNRESOLVED") {
    return <Spinner label="Chargement des autorisations" />;
  }

  if (createAccess !== "ALLOWED" || activeOrganization === undefined) {
    return (
      <ErrorState
        description="Votre accès ne permet pas de créer un magasin dans l’organisation active."
        title="Action non autorisée"
      />
    );
  }

  const createStore = async (input: StoreCreateInput) => {
    const store = await runSingleFlight(async () => {
      const createdStore = await create.mutateAsync(input);
      await refreshStoreContext();
      return createdStore;
    });
    if (store === undefined) {
      return;
    }

    notifyCreated();
    navigateToStore(store.id);
  };

  return (
    <section aria-labelledby="create-store-title">
      <h1 id="create-store-title">Créer un magasin</h1>
      <StoreCreateForm
        defaults={{
          currency: activeOrganization.defaultCurrency,
          locale: activeOrganization.defaultLocale,
          timeZone: activeOrganization.defaultTimeZone,
        }}
        onCreate={createStore}
      />
    </section>
  );
}
