"use client";
import { useQuery } from "@tanstack/react-query";
import { can } from "@zandu/authorization";
import { queryKeys } from "@zandu/server-state";

import { listCategories } from "../api/listCategories";

import type { FoundationApi } from "@zandu/api-client";
import type { EffectiveAccess } from "@zandu/authorization";

export function useCategoryOptions({
  api,
  access,
  organizationId,
}: Readonly<{
  api: FoundationApi | undefined;
  access: EffectiveAccess | undefined;
  organizationId: string | undefined;
}>) {
  const allowed = organizationId !== undefined && can(access, "CATALOG_READ", { organizationId });
  const query = useQuery({
    enabled: api !== undefined && allowed,
    queryKey: queryKeys.categories.list(organizationId ?? "unresolved-organization", {
      authorizationVersion: access?.authorizationVersion ?? 0,
    }),
    queryFn: () => {
      if (api === undefined || access === undefined || !allowed)
        throw new Error("Le contexte catégorie est indisponible.");
      return listCategories(api, access);
    },
  });
  return { ...query, allowed };
}
