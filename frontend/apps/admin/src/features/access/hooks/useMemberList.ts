"use client";

import { useQuery } from "@tanstack/react-query";
import { queryKeys } from "@zandu/server-state";

import { listMembers } from "../api/listMembers";

import type { FoundationApi } from "@zandu/api-client";

type MemberAccess = Parameters<FoundationApi["listOrganizationMembers"]>[0];

type UseMemberListOptions = Readonly<{
  access: MemberAccess | undefined;
  api: FoundationApi | undefined;
  organizationId: string | undefined;
}>;

export function useMemberList({ access, api, organizationId }: UseMemberListOptions) {
  const resolvedOrganizationId = organizationId ?? "unresolved-organization";
  const authorizationVersion = access?.authorizationVersion ?? 0;

  return useQuery({
    enabled: api !== undefined && access !== undefined && organizationId !== undefined,
    queryFn: async () => {
      if (api === undefined || access === undefined) {
        throw new Error("Le contexte d’accès aux membres est indisponible.");
      }

      return listMembers(api, access);
    },
    queryKey: queryKeys.members.list(resolvedOrganizationId, { authorizationVersion }),
  });
}
