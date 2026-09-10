"use client";

import { useCan, useEffectiveAccess } from "@zandu/authorization";
import { useNotifications } from "@zandu/notifications/react";
import { useOrganizationContext } from "@zandu/organization-context";
import { useState } from "react";

import { useAdminRuntime } from "../../../runtime/AdminRuntime";
import { useStoreDetails } from "../hooks/useStoreDetails";
import { useSuspendStore } from "../hooks/useSuspendStore";

import { StoreDetails } from "./StoreDetails";
import { SuspendStoreDialog } from "./SuspendStoreDialog";

export function StoreDetailsPage({ storeId }: Readonly<{ storeId: string }>) {
  const access = useEffectiveAccess();
  const { activeOrganizationId } = useOrganizationContext();
  const { api, queryClient, synchronizeStore } = useAdminRuntime();
  const notifications = useNotifications();
  const [isSuspendDialogOpen, setSuspendDialogOpen] = useState(false);
  const details = useStoreDetails({ access, api, organizationId: activeOrganizationId, storeId });
  const canEdit = useCan("STORE_UPDATE", { storeId });
  const canSuspend = useCan("STORE_SUSPEND", { storeId });
  const canClose = useCan("STORE_CLOSE", { storeId });
  const status = details.data?.status;
  const suspend = useSuspendStore({
    api,
    authorizationVersion: access?.authorizationVersion ?? 0,
    organizationId: activeOrganizationId ?? "unresolved-organization",
    queryClient,
    storeId,
  });

  const confirmSuspension = async () => {
    try {
      const store = await suspend.mutateAsync();
      synchronizeStore(store);
      notifications.notify({ message: "Magasin suspendu.", tone: "success" });
      setSuspendDialogOpen(false);
    } catch {
      // The dialog renders the mutation error while preserving its confirmation context.
    }
  };

  return (
    <>
      <StoreDetails
        actions={{
          edit: status === "ACTIVE" && canEdit,
          reactivate: status === "SUSPENDED" && canSuspend,
          requestClosure: (status === "ACTIVE" || status === "SUSPENDED") && canClose,
          suspend: status === "ACTIVE" && canSuspend,
        }}
        error={details.error}
        isLoading={details.isLoading}
        onSuspend={() => setSuspendDialogOpen(true)}
        store={details.data}
      />
      <SuspendStoreDialog
        error={suspend.error ?? undefined}
        isSuspending={suspend.isPending}
        onClose={() => setSuspendDialogOpen(false)}
        onConfirm={() => void confirmSuspension()}
        open={isSuspendDialogOpen}
        storeName={details.data?.name ?? "ce magasin"}
      />
    </>
  );
}
