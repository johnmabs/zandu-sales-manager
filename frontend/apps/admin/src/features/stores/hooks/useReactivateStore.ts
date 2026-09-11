"use client";

import { useMutation } from "@tanstack/react-query";
import { queryKeys } from "@zandu/server-state";

import { reactivateStore } from "../api/reactivateStore";

import type { QueryClient } from "@tanstack/react-query";
import type { FoundationApi } from "@zandu/api-client";

type Options = Readonly<{
  api: FoundationApi | undefined;
  authorizationVersion: number;
  organizationId: string;
  queryClient: QueryClient;
  storeId: string;
}>;

export function useReactivateStore({
  api,
  authorizationVersion,
  organizationId,
  queryClient,
  storeId,
}: Options) {
  return useMutation({
    mutationFn: async () => {
      if (api === undefined) {
        throw new Error("Le client de réactivation du magasin est indisponible.");
      }

      return reactivateStore(api, storeId);
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
