"use client";

import { useMutation } from "@tanstack/react-query";
import { queryKeys } from "@zandu/server-state";

import { cancelInvitation } from "../api/cancelInvitation";

import type { QueryClient } from "@tanstack/react-query";
import type { FoundationApi } from "@zandu/api-client";

type InvitationListContext = Readonly<{
  authorizationVersion: number;
  organizationId: string;
  queryClient: QueryClient;
}>;

export function useCancelInvitation(
  api: FoundationApi | undefined,
  listContext?: InvitationListContext,
) {
  return useMutation({
    mutationFn: async (invitationId: string) => {
      if (api === undefined) throw new Error("Le client d’annulation est indisponible.");
      return cancelInvitation(api, invitationId);
    },
    onSuccess: async () => {
      if (listContext === undefined) return;
      const queryKey = queryKeys.invitations.list(listContext.organizationId, {
        authorizationVersion: listContext.authorizationVersion,
      });
      await listContext.queryClient.invalidateQueries({ queryKey });
      await listContext.queryClient.refetchQueries({ queryKey });
    },
  });
}
