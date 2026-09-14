"use client";
import { useEffectiveAccess } from "@zandu/authorization";
import { useOrganizationContext } from "@zandu/organization-context";

import { useAdminRuntime } from "../../../runtime/AdminRuntime";

import { ProductListWorkspace } from "./ProductListWorkspace";

export function ProductListPage() {
  const access = useEffectiveAccess();
  const { activeOrganizationId } = useOrganizationContext();
  const { api } = useAdminRuntime();
  return (
    <ProductListWorkspace
      key={`${activeOrganizationId}:${access?.authorizationVersion}`}
      api={api}
      access={access}
      organizationId={activeOrganizationId}
    />
  );
}
