"use client";

import { useCan, useEffectiveAccess } from "@zandu/authorization";
import { useOrganizationContext } from "@zandu/organization-context";

import { useAdminRuntime } from "../../../runtime/AdminRuntime";
import { useStoreList } from "../hooks/useStoreList";

import { StoreList } from "./StoreList";

export function StoreListPage() {
  const access = useEffectiveAccess();
  const canCreate = useCan("STORE_CREATE");
  const { activeOrganizationId } = useOrganizationContext();
  const { api } = useAdminRuntime();
  const stores = useStoreList({ access, api, organizationId: activeOrganizationId });

  return (
    <section aria-labelledby="stores-page-title">
      <h1 id="stores-page-title">Magasins</h1>
      <StoreList
        canCreate={canCreate}
        error={stores.error}
        isLoading={stores.isLoading}
        stores={stores.data}
      />
    </section>
  );
}
