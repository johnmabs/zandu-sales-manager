"use client";
import { useMutation } from "@tanstack/react-query";

import type { FoundationApi } from "@zandu/api-client";
export function useSuspendMember(api: FoundationApi | undefined, membershipId: string) {
  return useMutation({
    mutationFn: async () => {
      if (api === undefined) throw new Error("Le client de suspension est indisponible.");
      return api.suspendMember(membershipId);
    },
  });
}
