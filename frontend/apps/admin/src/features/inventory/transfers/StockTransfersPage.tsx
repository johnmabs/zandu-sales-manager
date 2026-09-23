"use client";

import { useQuery } from "@tanstack/react-query";
import { can, useEffectiveAccess } from "@zandu/authorization";
import { useOrganizationContext } from "@zandu/organization-context";
import { queryKeys } from "@zandu/server-state";
import { ErrorState } from "@zandu/ui";
import { useState } from "react";

import { useAdminRuntime } from "../../../runtime/AdminRuntime";
import { listStockTransfers } from "../api/readInventory";

import { StockTransferList } from "./StockTransferList";

import type { FoundationApi } from "@zandu/api-client";
import type { EffectiveAccess } from "@zandu/authorization";
import type { AccessibleStore } from "@zandu/store-context";

const PAGE_SIZE = 25;

export function StockTransfersPage() {
  const access = useEffectiveAccess();
  const { activeOrganizationId } = useOrganizationContext();
  const { api, storeState } = useAdminRuntime();
  const store = storeState?.status === "ACTIVE" ? storeState.activeStore : undefined;

  return (
    <StockTransfersWorkspace
      key={`${activeOrganizationId}:${store?.id}:${access?.authorizationVersion}`}
      access={access}
      api={api}
      locale={store?.locale ?? "fr-FR"}
      organizationId={activeOrganizationId}
      storeId={store?.id}
      stores={storeState?.stores ?? []}
      timeZone={store?.timeZone ?? "UTC"}
    />
  );
}

export function StockTransfersWorkspace({
  access,
  api,
  locale,
  organizationId,
  storeId,
  stores,
  timeZone,
}: Readonly<{
  access: EffectiveAccess | undefined;
  api: FoundationApi | undefined;
  locale: string;
  organizationId: string | undefined;
  storeId: string | undefined;
  stores: readonly AccessibleStore[];
  timeZone: string;
}>) {
  const [cursorHistory, setCursorHistory] = useState<readonly string[]>([]);
  const cursor = cursorHistory.at(-1);
  const allowed =
    organizationId !== undefined &&
    storeId !== undefined &&
    can(access, "STOCK_TRANSFER_READ", { organizationId, storeId });
  const transfers = useQuery({
    enabled: api !== undefined && access !== undefined && allowed,
    queryKey: [
      ...queryKeys.stockTransfers.list(
        organizationId ?? "unresolved-organization",
        storeId ?? "unresolved-store",
      ),
      {
        authorizationVersion: access?.authorizationVersion ?? 0,
        cursor: cursor ?? "",
        limit: PAGE_SIZE,
      },
    ],
    queryFn: () => {
      if (api === undefined || access === undefined || !allowed) {
        throw new Error("Le contexte des transferts Stock est indisponible.");
      }
      return listStockTransfers(api, access, {
        ...(cursor === undefined ? {} : { cursor }),
        limit: PAGE_SIZE,
      });
    },
  });
  const pageNumber = cursorHistory.length + 1;

  if (!allowed) {
    return (
      <ErrorState
        title="Accès refusé"
        description="Votre accès ne permet pas de consulter les transferts de ce magasin."
      />
    );
  }

  return (
    <>
      {can(access, "STOCK_TRANSFER_CREATE", { organizationId, storeId }) ? (
        <a href="/admin/inventory/transfers/new">Nouveau transfert</a>
      ) : null}
      <StockTransferList
        {...(transfers.data === undefined ? {} : { page: transfers.data })}
        {...(transfers.error === null ? {} : { error: transfers.error })}
        isLoading={transfers.isLoading}
        locale={locale}
        onPageChange={(nextPage) => {
          if (nextPage < pageNumber) {
            setCursorHistory((history) => history.slice(0, Math.max(0, nextPage - 1)));
          } else if (nextPage === pageNumber + 1 && transfers.data?.nextCursor !== undefined) {
            setCursorHistory((history) => [...history, transfers.data!.nextCursor!]);
          }
        }}
        onRetry={() => void transfers.refetch()}
        pageNumber={pageNumber}
        pageSize={PAGE_SIZE}
        stores={stores}
        timeZone={timeZone}
      />
    </>
  );
}
