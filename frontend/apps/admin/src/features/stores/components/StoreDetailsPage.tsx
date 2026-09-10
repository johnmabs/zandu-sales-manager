"use client";

import { useEffectiveAccess } from "@zandu/authorization";
import { useNotifications } from "@zandu/notifications/react";
import { useOrganizationContext } from "@zandu/organization-context";
import { ErrorState, Spinner } from "@zandu/ui";
import { useState } from "react";

import { useAdminRuntime } from "../../../runtime/AdminRuntime";
import { useCancelStoreClosure } from "../hooks/useCancelStoreClosure";
import { useRequestStoreClosure } from "../hooks/useRequestStoreClosure";
import { useStoreDetails } from "../hooks/useStoreDetails";
import { useSuspendStore } from "../hooks/useSuspendStore";
import { resolveStoreAccess, storePermissions } from "../storeAuthorization";

import { CancelStoreClosureDialog } from "./CancelStoreClosureDialog";
import { RequestStoreClosureDialog } from "./RequestStoreClosureDialog";
import { StoreDetails } from "./StoreDetails";
import { SuspendStoreDialog } from "./SuspendStoreDialog";

import type { StoreClosureResource } from "@zandu/api-client";

export function StoreDetailsPage({ storeId }: Readonly<{ storeId: string }>) {
  const access = useEffectiveAccess();
  const { activeOrganizationId } = useOrganizationContext();
  const { api, queryClient, synchronizeStore } = useAdminRuntime();
  const notifications = useNotifications();
  const [isSuspendDialogOpen, setSuspendDialogOpen] = useState(false);
  const [isClosureDialogOpen, setClosureDialogOpen] = useState(false);
  const [isCancelClosureDialogOpen, setCancelClosureDialogOpen] = useState(false);
  const [closure, setClosure] = useState<StoreClosureResource>();
  const readAccess = resolveStoreAccess(
    access,
    activeOrganizationId,
    storePermissions.read,
    storeId,
  );
  const details = useStoreDetails({
    access: readAccess === "ALLOWED" ? access : undefined,
    api,
    organizationId: activeOrganizationId,
    storeId,
  });
  const canEdit =
    resolveStoreAccess(access, activeOrganizationId, storePermissions.update, storeId) ===
    "ALLOWED";
  const canSuspend =
    resolveStoreAccess(access, activeOrganizationId, storePermissions.suspend, storeId) ===
    "ALLOWED";
  const canClose =
    resolveStoreAccess(access, activeOrganizationId, storePermissions.close, storeId) === "ALLOWED";
  const status = details.data?.status;
  const suspend = useSuspendStore({
    api,
    authorizationVersion: access?.authorizationVersion ?? 0,
    organizationId: activeOrganizationId ?? "unresolved-organization",
    queryClient,
    storeId,
  });
  const requestClosure = useRequestStoreClosure({
    api,
    authorizationVersion: access?.authorizationVersion ?? 0,
    organizationId: activeOrganizationId ?? "unresolved-organization",
    queryClient,
    storeId,
  });
  const cancelClosure = useCancelStoreClosure({
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

  const confirmClosure = async (reason: string) => {
    try {
      const requestedClosure = await requestClosure.mutateAsync({ reason });
      setClosure(requestedClosure);
      try {
        const refreshed = await details.refetch();
        if (refreshed.data !== undefined) {
          synchronizeStore(refreshed.data);
        }
      } catch {
        // The workflow response remains authoritative if the refreshed Store projection is unavailable.
      }
      notifications.notify({ message: "Demande de fermeture enregistrée.", tone: "success" });
      setClosureDialogOpen(false);
    } catch {
      // The dialog renders the mutation error while preserving its confirmation context.
    }
  };

  const confirmClosureCancellation = async () => {
    try {
      const store = await cancelClosure.mutateAsync();
      synchronizeStore(store);
      setClosure(undefined);
      notifications.notify({ message: "Demande de fermeture annulée.", tone: "success" });
      setCancelClosureDialogOpen(false);
    } catch {
      // The dialog renders the mutation error while preserving its confirmation context.
    }
  };

  if (readAccess === "UNRESOLVED") {
    return <Spinner label="Chargement des autorisations" />;
  }

  if (readAccess === "OUT_OF_SCOPE") {
    return (
      <ErrorState
        description="Ce magasin est introuvable ou n’est pas accessible dans l’organisation active."
        title="Magasin introuvable"
      />
    );
  }

  if (readAccess === "DENIED") {
    return (
      <ErrorState
        description="Votre accès ne permet pas de consulter ce magasin."
        title="Accès refusé"
      />
    );
  }

  return (
    <>
      <StoreDetails
        actions={{
          cancelClosure: status === "CLOSURE_PENDING" && canClose,
          edit: status === "ACTIVE" && canEdit,
          reactivate: status === "SUSPENDED" && canSuspend,
          requestClosure: (status === "ACTIVE" || status === "SUSPENDED") && canClose,
          suspend: status === "ACTIVE" && canSuspend,
        }}
        {...(closure === undefined ? {} : { closure })}
        error={details.error}
        isLoading={details.isLoading}
        onCancelClosure={() => setCancelClosureDialogOpen(true)}
        onRequestClosure={() => setClosureDialogOpen(true)}
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
      <RequestStoreClosureDialog
        error={requestClosure.error ?? undefined}
        isRequesting={requestClosure.isPending}
        onClose={() => setClosureDialogOpen(false)}
        onConfirm={(reason) => void confirmClosure(reason)}
        open={isClosureDialogOpen}
        storeName={details.data?.name ?? "ce magasin"}
      />
      <CancelStoreClosureDialog
        error={cancelClosure.error ?? undefined}
        isCancelling={cancelClosure.isPending}
        onClose={() => setCancelClosureDialogOpen(false)}
        onConfirm={() => void confirmClosureCancellation()}
        open={isCancelClosureDialogOpen}
        storeName={details.data?.name ?? "ce magasin"}
      />
    </>
  );
}
