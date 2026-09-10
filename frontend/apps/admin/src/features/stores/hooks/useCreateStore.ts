"use client";

import { useMutation } from "@tanstack/react-query";
import { queryKeys } from "@zandu/server-state";

import { createStore } from "../api/createStore";

import type { QueryClient } from "@tanstack/react-query";
import type { FoundationApi, StoreCreateInput } from "@zandu/api-client";

type UseCreateStoreOptions = Readonly<{
  api: FoundationApi | undefined;
  authorizationVersion: number;
  organizationId: string;
  queryClient: QueryClient;
}>;

export function useCreateStore({
  api,
  authorizationVersion,
  organizationId,
  queryClient,
}: UseCreateStoreOptions) {
  return useMutation({
    mutationFn: async (input: StoreCreateInput) => {
      if (api === undefined) {
        throw new Error("Le client de création de magasin est indisponible.");
      }

      return createStore(api, input);
    },
    onSuccess: async () => {
      const queryKey = queryKeys.stores.list(organizationId, { authorizationVersion });

      await queryClient.invalidateQueries({ queryKey });
      await queryClient.refetchQueries({ queryKey });
    },
  });
}
