"use client";

import { useQuery } from "@tanstack/react-query";
import { queryKeys } from "@zandu/server-state";

import { listInvitations } from "../api/listInvitations";

import type { FoundationApi } from "@zandu/api-client";

type InvitationAccess = Parameters<FoundationApi["listOrganizationInvitations"]>[0];

export function useInvitationList({
  access,
  api,
  organizationId,
}: Readonly<{
  access: InvitationAccess | undefined;
  api: FoundationApi | undefined;
  organizationId: string | undefined;
}>) {
  const resolvedOrganizationId = organizationId ?? "unresolved-organization";
  const authorizationVersion = access?.authorizationVersion ?? 0;

  return useQuery({
    enabled: api !== undefined && access !== undefined && organizationId !== undefined,
    queryFn: async () => {
      if (api === undefined || access === undefined) {
        throw new Error("Le contexte d’accès aux invitations est indisponible.");
      }
      return listInvitations(api, access);
    },
    queryKey: queryKeys.invitations.list(resolvedOrganizationId, { authorizationVersion }),
  });
}
