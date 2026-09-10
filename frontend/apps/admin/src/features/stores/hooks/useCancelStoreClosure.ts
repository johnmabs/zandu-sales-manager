"use client";

import { useMutation } from "@tanstack/react-query";
import { queryKeys } from "@zandu/server-state";

import { cancelStoreClosure } from "../api/cancelStoreClosure";

import type { QueryClient } from "@tanstack/react-query";
import type { FoundationApi } from "@zandu/api-client";

type UseCancelStoreClosureOptions = Readonly<{
  api: FoundationApi | undefined;
  authorizationVersion: number;
  organizationId: string;
  queryClient: QueryClient;
  storeId: string;
}>;

export function useCancelStoreClosure({
  api,
  authorizationVersion,
  organizationId,
  queryClient,
  storeId,
}: UseCancelStoreClosureOptions) {
  return useMutation({
    mutationFn: async () => {
      if (api === undefined) {
        throw new Error("Le client d’annulation de fermeture est indisponible.");
      }

      return cancelStoreClosure(api, storeId);
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
