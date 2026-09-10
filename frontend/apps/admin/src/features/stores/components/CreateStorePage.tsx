"use client";

import { useCan, useEffectiveAccess } from "@zandu/authorization";
import { useNotifications } from "@zandu/notifications/react";
import { useOrganizationContext } from "@zandu/organization-context";
import { ErrorState } from "@zandu/ui";
import { useRouter } from "next/navigation";

import { useAdminRuntime } from "../../../runtime/AdminRuntime";
import { useCreateStore } from "../hooks/useCreateStore";

import { StoreCreateForm } from "./StoreCreateForm";

import type { StoreCreateInput } from "@zandu/api-client";

export function CreateStorePage() {
  const access = useEffectiveAccess();
  const canCreate = useCan("STORE_CREATE");
  const { activeOrganization } = useOrganizationContext();
  const { api, queryClient } = useAdminRuntime();
  const notifications = useNotifications();
  const router = useRouter();
  const create = useCreateStore({
    api,
    authorizationVersion: access?.authorizationVersion ?? 0,
    organizationId: activeOrganization?.id ?? "unresolved-organization",
    queryClient,
  });

  if (!canCreate || activeOrganization === undefined) {
    return (
      <ErrorState
        description="Votre accès ne permet pas de créer un magasin dans l’organisation active."
        title="Action non autorisée"
      />
    );
  }

  const createStore = async (input: StoreCreateInput) => {
    const store = await create.mutateAsync(input);

    notifications.notify({ message: "Magasin créé.", tone: "success" });
    router.push(`/app/stores/${encodeURIComponent(store.id)}`);
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
