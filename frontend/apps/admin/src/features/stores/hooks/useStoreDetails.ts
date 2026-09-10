"use client";

import { useQuery } from "@tanstack/react-query";
import { queryKeys } from "@zandu/server-state";

import { getStore } from "../api/getStore";

import type { FoundationApi } from "@zandu/api-client";

type StoreAccess = Parameters<FoundationApi["getAccessibleStore"]>[1];

type UseStoreDetailsOptions = Readonly<{
  access: StoreAccess | undefined;
  api: FoundationApi | undefined;
  organizationId: string | undefined;
  storeId: string;
}>;

export function useStoreDetails({ access, api, organizationId, storeId }: UseStoreDetailsOptions) {
  const resolvedOrganizationId = organizationId ?? "unresolved-organization";
  const authorizationVersion = access?.authorizationVersion ?? 0;

  return useQuery({
    enabled: api !== undefined && access !== undefined && organizationId !== undefined,
    queryFn: async () => {
      if (api === undefined || access === undefined) {
        throw new Error("Le contexte d’accès au magasin est indisponible.");
      }

      return getStore(api, storeId, access);
    },
    queryKey: queryKeys.stores.detail(resolvedOrganizationId, storeId, { authorizationVersion }),
  });
}
