"use client";

import { useEffectiveAccess } from "@zandu/authorization";
import { useOrganizationContext } from "@zandu/organization-context";

import { useAdminRuntime } from "../../../runtime/AdminRuntime";

import { StockPositionsWorkspace } from "./StockPositionsWorkspace";

export function StockPositionsPage() {
  const access = useEffectiveAccess();
  const { activeOrganizationId } = useOrganizationContext();
  const { api, storeState } = useAdminRuntime();
  const store = storeState?.status === "ACTIVE" ? storeState.activeStore : undefined;

  return (
    <StockPositionsWorkspace
      key={`${activeOrganizationId}:${store?.id}:${access?.authorizationVersion}`}
      access={access}
      api={api}
      locale={store?.locale ?? "fr-FR"}
      organizationId={activeOrganizationId}
      storeId={store?.id}
    />
  );
}
