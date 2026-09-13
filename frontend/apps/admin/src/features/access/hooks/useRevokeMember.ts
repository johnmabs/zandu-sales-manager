"use client";
import { useMutation } from "@tanstack/react-query";
import type { FoundationApi } from "@zandu/api-client";
export function useRevokeMember(api: FoundationApi | undefined, membershipId: string) {
  return useMutation({
    mutationFn: async () => {
      if (api === undefined) throw new Error("Le client de révocation est indisponible.");
      return api.revokeMember(membershipId);
    },
  });
}
