"use client";

import { useQuery } from "@tanstack/react-query";
import { queryKeys } from "@zandu/server-state";

import { listStores } from "../api/listStores";

import type { FoundationApi } from "@zandu/api-client";

type StoreAccess = Parameters<FoundationApi["listAccessibleStores"]>[0];

type UseStoreListOptions = Readonly<{
  access: StoreAccess | undefined;
  api: FoundationApi | undefined;
  organizationId: string | undefined;
}>;

export function useStoreList({ access, api, organizationId }: UseStoreListOptions) {
  const resolvedOrganizationId = organizationId ?? "unresolved-organization";
  const authorizationVersion = access?.authorizationVersion ?? 0;

  return useQuery({
    enabled: api !== undefined && access !== undefined && organizationId !== undefined,
    queryFn: async () => {
      if (api === undefined || access === undefined) {
        throw new Error("Le contexte d’accès aux magasins est indisponible.");
      }

      return listStores(api, access);
    },
    queryKey: queryKeys.stores.list(resolvedOrganizationId, { authorizationVersion }),
  });
}
