"use client";
import { useMutation } from "@tanstack/react-query";
import type { FoundationApi } from "@zandu/api-client";
export function useReactivateMember(api: FoundationApi | undefined, membershipId: string) {
  return useMutation({
    mutationFn: async () => {
      if (api === undefined) throw new Error("Le client de réactivation est indisponible.");
      return api.reactivateMember(membershipId);
    },
  });
}
