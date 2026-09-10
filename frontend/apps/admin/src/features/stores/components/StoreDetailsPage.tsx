"use client";

import { useCan, useEffectiveAccess } from "@zandu/authorization";
import { useOrganizationContext } from "@zandu/organization-context";

import { useAdminRuntime } from "../../../runtime/AdminRuntime";
import { useStoreDetails } from "../hooks/useStoreDetails";

import { StoreDetails } from "./StoreDetails";

export function StoreDetailsPage({ storeId }: Readonly<{ storeId: string }>) {
  const access = useEffectiveAccess();
  const { activeOrganizationId } = useOrganizationContext();
  const { api } = useAdminRuntime();
  const details = useStoreDetails({ access, api, organizationId: activeOrganizationId, storeId });
  const canEdit = useCan("STORE_UPDATE", { storeId });
  const canSuspend = useCan("STORE_SUSPEND", { storeId });
  const canClose = useCan("STORE_CLOSE", { storeId });
  const status = details.data?.status;

  return (
    <StoreDetails
      actions={{
        edit: status === "ACTIVE" && canEdit,
        reactivate: status === "SUSPENDED" && canSuspend,
        requestClosure: (status === "ACTIVE" || status === "SUSPENDED") && canClose,
        suspend: status === "ACTIVE" && canSuspend,
      }}
      error={details.error}
      isLoading={details.isLoading}
      store={details.data}
    />
  );
}
