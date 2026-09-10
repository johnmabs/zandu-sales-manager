"use client";

import { useMutation } from "@tanstack/react-query";
import { queryKeys } from "@zandu/server-state";

import { updateStore } from "../api/updateStore";

import type { QueryClient } from "@tanstack/react-query";
import type { FoundationApi, StoreUpdateInput } from "@zandu/api-client";

type UseUpdateStoreOptions = Readonly<{
  api: FoundationApi | undefined;
  authorizationVersion: number;
  organizationId: string;
  queryClient: QueryClient;
  storeId: string;
}>;

export function useUpdateStore({
  api,
  authorizationVersion,
  organizationId,
  queryClient,
  storeId,
}: UseUpdateStoreOptions) {
  return useMutation({
    mutationFn: async (input: StoreUpdateInput) => {
      if (api === undefined) {
        throw new Error("Le client de mise à jour du magasin est indisponible.");
      }

      return updateStore(api, storeId, input);
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
