"use client";

import { useMutation } from "@tanstack/react-query";
import { can, useEffectiveAccess } from "@zandu/authorization";
import { useNotifications } from "@zandu/notifications/react";
import { useOrganizationContext } from "@zandu/organization-context";
import { queryKeys } from "@zandu/server-state";
import { ErrorState, Spinner } from "@zandu/ui";
import { useRouter } from "next/navigation";

import { useAdminRuntime } from "../../../runtime/AdminRuntime";
import { createStockTransfer } from "../api/readInventory";
import { hasUnknownInventoryMutationOutcome } from "../mutationSafety";

import { CreateStockTransferForm } from "./CreateStockTransferForm";

import type { QueryClient } from "@tanstack/react-query";
import type {
  FoundationApi,
  StockTransferCreateInput,
  StockTransferResource,
} from "@zandu/api-client";
import type { EffectiveAccess } from "@zandu/authorization";
import type { AccessibleStore } from "@zandu/store-context";

export function CreateStockTransferPage() {
  const access = useEffectiveAccess();
  const { activeOrganizationId } = useOrganizationContext();
  const { api, queryClient, storeState } = useAdminRuntime();
  const router = useRouter();
  const notifications = useNotifications();
  const activeStore = storeState?.status === "ACTIVE" ? storeState.activeStore : undefined;

  return (
    <CreateStockTransferWorkspace
      access={access}
      activeStoreId={activeStore?.id}
      api={api}
      onCreated={(transfer) => {
        notifications.notify({ message: "Transfert créé en brouillon.", tone: "success" });
        router.push(`/admin/inventory/transfers/${encodeURIComponent(transfer.id)}`);
      }}
      organizationId={activeOrganizationId}
      queryClient={queryClient}
      stores={storeState?.stores ?? []}
    />
  );
}

export function CreateStockTransferWorkspace({
  access,
  activeStoreId,
  api,
  onCreated,
  organizationId,
  queryClient,
  stores,
}: Readonly<{
  access: EffectiveAccess | undefined;
  activeStoreId: string | undefined;
  api: FoundationApi | undefined;
  onCreated: (transfer: StockTransferResource) => void;
  organizationId: string | undefined;
  queryClient: QueryClient;
  stores: readonly AccessibleStore[];
}>) {
  const eligibleStores = stores.filter(
    (store) =>
      store.status === "ACTIVE" &&
      organizationId !== undefined &&
      can(access, "STOCK_TRANSFER_CREATE", { organizationId, storeId: store.id }),
  );
  const create = useMutation({
    mutationFn: (input: StockTransferCreateInput) => {
      if (api === undefined || access === undefined) {
        throw new Error("Le contexte de création du transfert est indisponible.");
      }
      return createStockTransfer(api, input, access);
    },
    onError: async (error) => {
      if (!hasUnknownInventoryMutationOutcome(error)) return;
      await queryClient.invalidateQueries({
        queryKey: ["stockTransfers", organizationId ?? "unresolved-organization"],
      });
    },
    onSuccess: async (transfer) => {
      await queryClient.invalidateQueries({
        queryKey: ["stockTransfers", organizationId ?? "unresolved-organization"],
      });
      queryClient.setQueriesData(
        {
          queryKey: queryKeys.stockTransfers.detail(
            organizationId ?? "unresolved-organization",
            transfer.id,
          ),
        },
        transfer,
      );
      onCreated(transfer);
    },
  });

  if (
    access === undefined ||
    api === undefined ||
    organizationId === undefined ||
    activeStoreId === undefined
  ) {
    return <Spinner label="Chargement de la création du transfert" />;
  }
  if (eligibleStores.length < 2) {
    return (
      <ErrorState
        title="Deux magasins requis"
        description="La création exige au moins deux magasins opérationnels accessibles avec la permission de créer un transfert."
      />
    );
  }
  const defaultSourceStoreId = eligibleStores.some((store) => store.id === activeStoreId)
    ? activeStoreId
    : (eligibleStores[0]?.id ?? "");
  return (
    <CreateStockTransferForm
      defaultSourceStoreId={defaultSourceStoreId}
      onCreate={(input) => create.mutateAsync(input).then(() => undefined)}
      stores={eligibleStores}
    />
  );
}
