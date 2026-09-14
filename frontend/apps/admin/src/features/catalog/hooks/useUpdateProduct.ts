"use client";

import { useMutation } from "@tanstack/react-query";
import { queryKeys } from "@zandu/server-state";

import { updateProduct } from "../api/updateProduct";

import type { QueryClient } from "@tanstack/react-query";
import type { FoundationApi, ProductUpdateInput } from "@zandu/api-client";

export function useUpdateProduct({
  api,
  authorizationVersion,
  organizationId,
  productId,
  queryClient,
}: Readonly<{
  api: FoundationApi | undefined;
  authorizationVersion: number;
  organizationId: string;
  productId: string;
  queryClient: QueryClient;
}>) {
  return useMutation({
    mutationFn: (input: ProductUpdateInput) => {
      if (!api) throw new Error("Le client de modification produit est indisponible.");
      return updateProduct(api, productId, input);
    },
    onSuccess: async () => {
      const detailKey = queryKeys.products.detail(organizationId, productId);
      const listKey = queryKeys.products.list(organizationId, { authorizationVersion });
      await queryClient.invalidateQueries({ queryKey: detailKey });
      await queryClient.invalidateQueries({ queryKey: listKey });
      await queryClient.refetchQueries({ queryKey: detailKey });
      await queryClient.refetchQueries({ queryKey: listKey });
    },
  });
}
