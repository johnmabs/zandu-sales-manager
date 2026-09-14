"use client";
import { useQuery } from "@tanstack/react-query";
import { can } from "@zandu/authorization";
import { queryKeys } from "@zandu/server-state";

import { listProducts } from "../api/listProducts";

import type { FoundationApi, ProductFilters } from "@zandu/api-client";
import type { EffectiveAccess } from "@zandu/authorization";

export function useProductList({
  api,
  access,
  organizationId,
  filters = {},
}: Readonly<{
  api: FoundationApi | undefined;
  access: EffectiveAccess | undefined;
  organizationId: string | undefined;
  filters?: ProductFilters;
}>) {
  const allowed = organizationId !== undefined && can(access, "PRODUCT_READ", { organizationId });
  return useQuery({
    enabled: api !== undefined && allowed,
    queryKey: queryKeys.products.list(organizationId ?? "unresolved-organization", {
      ...filters,
      authorizationVersion: access?.authorizationVersion ?? 0,
    }),
    queryFn: () => {
      if (api === undefined || access === undefined || !allowed)
        throw new Error("Le contexte produit est indisponible.");
      return listProducts(api, access, filters);
    },
  });
}
