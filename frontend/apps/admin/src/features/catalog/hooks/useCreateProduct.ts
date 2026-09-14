"use client";

import { useMutation } from "@tanstack/react-query";
import { queryKeys } from "@zandu/server-state";

import { createProduct } from "../api/createProduct";

import type { QueryClient } from "@tanstack/react-query";
import type { FoundationApi, ProductCreateInput } from "@zandu/api-client";

export function useCreateProduct({
  api,
  authorizationVersion,
  organizationId,
  queryClient,
}: Readonly<{
  api: FoundationApi | undefined;
  authorizationVersion: number;
  organizationId: string;
  queryClient: QueryClient;
}>) {
  return useMutation({
    mutationFn: (input: ProductCreateInput) => {
      if (api === undefined) throw new Error("Le client de création produit est indisponible.");
      return createProduct(api, input);
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({
        queryKey: queryKeys.products.list(organizationId, { authorizationVersion }),
      });
    },
  });
}
