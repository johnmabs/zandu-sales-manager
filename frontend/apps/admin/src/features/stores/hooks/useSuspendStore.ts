"use client";

import { useMutation } from "@tanstack/react-query";
import { queryKeys } from "@zandu/server-state";

import { suspendStore } from "../api/suspendStore";

import type { QueryClient } from "@tanstack/react-query";
import type { FoundationApi } from "@zandu/api-client";

type UseSuspendStoreOptions = Readonly<{
  api: FoundationApi | undefined;
  authorizationVersion: number;
  organizationId: string;
  queryClient: QueryClient;
  storeId: string;
}>;

export function useSuspendStore({
  api,
  authorizationVersion,
  organizationId,
  queryClient,
  storeId,
}: UseSuspendStoreOptions) {
  return useMutation({
    mutationFn: async () => {
      if (api === undefined) {
        throw new Error("Le client de suspension du magasin est indisponible.");
      }

      return suspendStore(api, storeId);
    },
    onSuccess: async () => {
      const filters = { authorizationVersion };
      const detailQueryKey = queryKeys.stores.detail(organizationId, storeId, filters);
      const listQueryKey = queryKeys.stores.list(organizationId, filters);

      await queryClient.invalidateQueries({ queryKey: detailQueryKey });
      await queryClient.invalidateQueries({ queryKey: listQueryKey });
      await queryClient.refetchQueries({ queryKey: detailQueryKey });
      await queryClient.refetchQueries({ queryKey: listQueryKey });
    },
  });
}
