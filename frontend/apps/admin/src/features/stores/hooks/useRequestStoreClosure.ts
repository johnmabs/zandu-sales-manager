"use client";

import { useMutation } from "@tanstack/react-query";
import { queryKeys } from "@zandu/server-state";

import { requestStoreClosure } from "../api/requestStoreClosure";

import type { QueryClient } from "@tanstack/react-query";
import type { FoundationApi, StoreClosureRequestInput } from "@zandu/api-client";

type UseRequestStoreClosureOptions = Readonly<{
  api: FoundationApi | undefined;
  authorizationVersion: number;
  organizationId: string;
  queryClient: QueryClient;
  storeId: string;
}>;

export function useRequestStoreClosure({
  api,
  authorizationVersion,
  organizationId,
  queryClient,
  storeId,
}: UseRequestStoreClosureOptions) {
  return useMutation({
    mutationFn: async (input: StoreClosureRequestInput) => {
      if (api === undefined) {
        throw new Error("Le client de demande de fermeture est indisponible.");
      }

      return requestStoreClosure(api, storeId, input);
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
