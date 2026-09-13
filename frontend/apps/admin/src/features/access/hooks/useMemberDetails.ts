"use client";

import { useQuery } from "@tanstack/react-query";
import { queryKeys } from "@zandu/server-state";

import { getMember } from "../api/getMember";

import type { FoundationApi, MembershipResource } from "@zandu/api-client";

type MemberAccess = Parameters<FoundationApi["getOrganizationMember"]>[1];

type UseMemberDetailsOptions = Readonly<{
  access: MemberAccess | undefined;
  api: FoundationApi | undefined;
  membershipId: string;
  organizationId: string | undefined;
}>;

export function useMemberDetails({
  access,
  api,
  membershipId,
  organizationId,
}: UseMemberDetailsOptions) {
  const resolvedOrganizationId = organizationId ?? "unresolved-organization";
  const authorizationVersion = access?.authorizationVersion ?? 0;

  return useQuery<MembershipResource | undefined>({
    enabled: api !== undefined && access !== undefined && organizationId !== undefined,
    queryFn: async () => {
      if (api === undefined || access === undefined) {
        throw new Error("Le contexte d’accès au membre est indisponible.");
      }

      return getMember(api, membershipId, access);
    },
    queryKey: queryKeys.members.detail(resolvedOrganizationId, membershipId, {
      authorizationVersion,
    }),
  });
}
