"use client";

import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { createElement } from "react";

import type { QueryKey } from "@tanstack/react-query";
import type { ReactNode } from "react";

export type QueryParameters = Readonly<
  Record<string, boolean | null | number | string | readonly (boolean | number | string)[]>
>;

export type ServerStateProviderProps = Readonly<{
  children: ReactNode;
  client: QueryClient;
}>;

const organizationScopedResources = new Set([
  "organizations",
  "stores",
  "products",
  "stock",
  "suppliers",
  "purchaseOrders",
  "sales",
]);

/**
 * Central query-key factory. Every tenant-dependent key places organizationId
 * second, and every store-dependent key places storeId third.
 */
export const queryKeys = {
  organizations: {
    all: () => ["organizations"] as const,
    detail: (organizationId: string) => ["organizations", organizationId] as const,
  },
  products: {
    detail: (organizationId: string, productId: string) =>
      ["products", organizationId, productId] as const,
    list: (organizationId: string, filters?: QueryParameters) =>
      filters === undefined
        ? (["products", organizationId] as const)
        : (["products", organizationId, filters] as const),
  },
  purchaseOrders: {
    detail: (organizationId: string, purchaseOrderId: string) =>
      ["purchaseOrders", organizationId, purchaseOrderId] as const,
    list: (organizationId: string, filters?: QueryParameters) =>
      filters === undefined
        ? (["purchaseOrders", organizationId] as const)
        : (["purchaseOrders", organizationId, filters] as const),
  },
  sales: {
    detail: (organizationId: string, storeId: string, saleId: string) =>
      ["sales", organizationId, storeId, saleId] as const,
    list: (organizationId: string, storeId: string, filters?: QueryParameters) =>
      filters === undefined
        ? (["sales", organizationId, storeId] as const)
        : (["sales", organizationId, storeId, filters] as const),
  },
  stock: {
    list: (organizationId: string, storeId: string, filters?: QueryParameters) =>
      filters === undefined
        ? (["stock", organizationId, storeId] as const)
        : (["stock", organizationId, storeId, filters] as const),
  },
  stores: {
    detail: (organizationId: string, storeId: string) =>
      ["stores", organizationId, storeId] as const,
    list: (organizationId: string, filters?: QueryParameters) =>
      filters === undefined
        ? (["stores", organizationId] as const)
        : (["stores", organizationId, filters] as const),
  },
  suppliers: {
    detail: (organizationId: string, supplierId: string) =>
      ["suppliers", organizationId, supplierId] as const,
    list: (organizationId: string, filters?: QueryParameters) =>
      filters === undefined
        ? (["suppliers", organizationId] as const)
        : (["suppliers", organizationId, filters] as const),
  },
} as const;

export function createServerStateClient(): QueryClient {
  return new QueryClient({
    defaultOptions: {
      mutations: {
        retry: false,
      },
      queries: {
        retry: 2,
        staleTime: 30_000,
      },
    },
  });
}

export function ServerStateProvider({ children, client }: ServerStateProviderProps) {
  return createElement(QueryClientProvider, { client }, children);
}

/**
 * Removes cached entries belonging to another organization, then invalidates
 * retained entries for the newly active organization. UI state is deliberately
 * not managed here.
 */
export async function transitionOrganizationCache(
  client: QueryClient,
  organizationId: string,
): Promise<void> {
  client.removeQueries({
    predicate: (query) =>
      isOrganizationScopedQuery(query.queryKey) && query.queryKey[1] !== organizationId,
  });

  await client.invalidateQueries({
    predicate: (query) =>
      isOrganizationScopedQuery(query.queryKey) && query.queryKey[1] === organizationId,
  });
}

function isOrganizationScopedQuery(queryKey: QueryKey): boolean {
  const resource = queryKey[0];

  return typeof resource === "string" && organizationScopedResources.has(resource);
}
