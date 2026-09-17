"use client";

import { useQuery } from "@tanstack/react-query";
import { can } from "@zandu/authorization";
import { queryKeys } from "@zandu/server-state";

import { listStocks } from "../api/readInventory";

import type { FoundationApi, ProductFilters } from "@zandu/api-client";
import type { EffectiveAccess } from "@zandu/authorization";

type StockPositionQuery = Readonly<{
  access: EffectiveAccess | undefined;
  api: FoundationApi | undefined;
  organizationId: string | undefined;
  storeId: string | undefined;
}>;

export function useStockPositions({ api, access, organizationId, storeId }: StockPositionQuery) {
  const allowed =
    organizationId !== undefined &&
    storeId !== undefined &&
    can(access, "INVENTORY_READ", { organizationId, storeId });

  return useQuery({
    enabled: api !== undefined && allowed,
    queryKey: queryKeys.stock.list(
      organizationId ?? "unresolved-organization",
      storeId ?? "unresolved-store",
      { authorizationVersion: access?.authorizationVersion ?? 0 },
    ),
    queryFn: () => {
      if (api === undefined || access === undefined || storeId === undefined || !allowed) {
        throw new Error("Le contexte Stock est indisponible.");
      }
      return listStocks(api, storeId, access);
    },
  });
}

export function useStockProducts({
  api,
  access,
  filters,
  organizationId,
  storeId,
}: StockPositionQuery & Readonly<{ filters: ProductFilters }>) {
  const metadataAllowed =
    organizationId !== undefined && can(access, "PRODUCT_READ", { organizationId });
  const inventoryAllowed =
    organizationId !== undefined &&
    storeId !== undefined &&
    can(access, "INVENTORY_READ", { organizationId, storeId });

  return {
    allowed: metadataAllowed,
    query: useQuery({
      enabled: api !== undefined && metadataAllowed && inventoryAllowed,
      queryKey: queryKeys.products.list(organizationId ?? "unresolved-organization", {
        ...filters,
        authorizationVersion: access?.authorizationVersion ?? 0,
      }),
      queryFn: () => {
        if (api === undefined || access === undefined || !metadataAllowed || !inventoryAllowed) {
          throw new Error("Le contexte Produit est indisponible.");
        }
        return api.listProducts(access, filters);
      },
    }),
  };
}
