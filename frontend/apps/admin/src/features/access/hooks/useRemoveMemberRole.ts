"use client";

import { useMutation } from "@tanstack/react-query";

import type { FoundationApi } from "@zandu/api-client";

export function useRemoveMemberRole(api: FoundationApi | undefined, membershipId: string) {
  return useMutation({
    mutationFn: async (assignmentId: string) => {
      if (api === undefined) throw new Error("Le client de retrait est indisponible.");
      return api.removeMemberRole(membershipId, assignmentId);
    },
  });
}
