"use client";

import { useQuery } from "@tanstack/react-query";
import { queryKeys } from "@zandu/server-state";

import { listRoles } from "../api/listRoles";

import type { FoundationApi } from "@zandu/api-client";

type UseRoleCatalogOptions = Readonly<{
  api: FoundationApi | undefined;
  authorizationVersion: number | undefined;
  organizationId: string | undefined;
}>;

export function useRoleCatalog({
  api,
  authorizationVersion,
  organizationId,
}: UseRoleCatalogOptions) {
  const resolvedOrganizationId = organizationId ?? "unresolved-organization";

  return useQuery({
    enabled:
      api !== undefined && authorizationVersion !== undefined && organizationId !== undefined,
    queryFn: async () => {
      if (api === undefined) {
        throw new Error("Le client des rôles est indisponible.");
      }

      return listRoles(api);
    },
    queryKey: queryKeys.roles.list(resolvedOrganizationId, {
      authorizationVersion: authorizationVersion ?? 0,
    }),
  });
}
