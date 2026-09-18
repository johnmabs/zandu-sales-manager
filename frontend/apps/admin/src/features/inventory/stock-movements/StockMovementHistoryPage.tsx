"use client";

import { useQuery } from "@tanstack/react-query";
import { can, useEffectiveAccess } from "@zandu/authorization";
import { useOrganizationContext } from "@zandu/organization-context";
import { queryKeys } from "@zandu/server-state";
import { ErrorState } from "@zandu/ui";
import { useState } from "react";

import { useAdminRuntime } from "../../../runtime/AdminRuntime";
import { listStockMovementPage } from "../api/readInventory";

import { StockMovementFiltersForm } from "./StockMovementFiltersForm";
import { StockMovementList } from "./StockMovementList";

import type { FoundationApi } from "@zandu/api-client";
import type { EffectiveAccess } from "@zandu/authorization";

const PAGE_SIZE = 25;

export function StockMovementHistoryPage({
  initialProductId,
}: Readonly<{ initialProductId?: string }>) {
  const access = useEffectiveAccess();
  const { activeOrganizationId } = useOrganizationContext();
  const { api, storeState } = useAdminRuntime();
  const store = storeState?.status === "ACTIVE" ? storeState.activeStore : undefined;

  return (
    <StockMovementHistoryWorkspace
      key={`${activeOrganizationId}:${store?.id}:${access?.authorizationVersion}:${initialProductId ?? "all"}`}
      access={access}
      api={api}
      {...(initialProductId === undefined ? {} : { initialProductId })}
      locale={store?.locale ?? "fr-FR"}
      organizationId={activeOrganizationId}
      storeId={store?.id}
      timeZone={store?.timeZone ?? "UTC"}
    />
  );
}

export function StockMovementHistoryWorkspace({
  access,
  api,
  initialProductId,
  locale,
  organizationId,
  storeId,
  timeZone,
}: Readonly<{
  access: EffectiveAccess | undefined;
  api: FoundationApi | undefined;
  initialProductId?: string;
  locale: string;
  organizationId: string | undefined;
  storeId: string | undefined;
  timeZone: string;
}>) {
  const [productId, setProductId] = useState(initialProductId);
  const [cursorHistory, setCursorHistory] = useState<readonly string[]>([]);
  const cursor = cursorHistory.at(-1);
  const allowed =
    organizationId !== undefined &&
    storeId !== undefined &&
    can(access, "STOCK_MOVEMENT_READ", { organizationId, storeId });
  const movements = useQuery({
    enabled: api !== undefined && access !== undefined && allowed,
    queryKey: queryKeys.stockMovements.list(
      organizationId ?? "unresolved-organization",
      storeId ?? "unresolved-store",
      {
        authorizationVersion: access?.authorizationVersion ?? 0,
        cursor: cursor ?? "",
        limit: PAGE_SIZE,
        productId: productId ?? "",
      },
    ),
    queryFn: () => {
      if (api === undefined || access === undefined || storeId === undefined || !allowed) {
        throw new Error("Le contexte des mouvements Stock est indisponible.");
      }
      return listStockMovementPage(api, storeId, access, {
        ...(cursor === undefined ? {} : { cursor }),
        limit: PAGE_SIZE,
        ...(productId === undefined ? {} : { productId }),
      });
    },
  });

  const applyProduct = (nextProductId: string | undefined) => {
    setProductId(nextProductId);
    setCursorHistory([]);
  };
  const pageNumber = cursorHistory.length + 1;

  if (!allowed) {
    return (
      <ErrorState
        title="Accès refusé"
        description="Votre accès ne permet pas de consulter les mouvements de ce magasin."
      />
    );
  }

  return (
    <>
      <StockMovementFiltersForm
        {...(initialProductId === undefined ? {} : { initialProductId })}
        onApply={applyProduct}
        onReset={() => applyProduct(undefined)}
      />
      <StockMovementList
        {...(movements.data === undefined ? {} : { page: movements.data })}
        {...(movements.error === null ? {} : { error: movements.error })}
        isLoading={movements.isLoading}
        locale={locale}
        onPageChange={(nextPage) => {
          if (nextPage < pageNumber) {
            setCursorHistory((history) => history.slice(0, Math.max(0, nextPage - 1)));
          } else if (nextPage === pageNumber + 1 && movements.data?.nextCursor !== undefined) {
            setCursorHistory((history) => [...history, movements.data!.nextCursor!]);
          }
        }}
        onRetry={() => void movements.refetch()}
        pageNumber={pageNumber}
        pageSize={PAGE_SIZE}
        timeZone={timeZone}
      />
    </>
  );
}
