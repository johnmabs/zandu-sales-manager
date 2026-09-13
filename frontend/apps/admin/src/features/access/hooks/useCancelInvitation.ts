"use client";

import { useMutation } from "@tanstack/react-query";

import { cancelInvitation } from "../api/cancelInvitation";

import type { FoundationApi } from "@zandu/api-client";

export function useCancelInvitation(api: FoundationApi | undefined) {
  return useMutation({
    mutationFn: async (invitationId: string) => {
      if (api === undefined) throw new Error("Le client d’annulation est indisponible.");
      return cancelInvitation(api, invitationId);
    },
  });
}
