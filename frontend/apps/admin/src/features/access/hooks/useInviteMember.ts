"use client";

import { useMutation } from "@tanstack/react-query";

import { inviteMember } from "../api/inviteMember";

import type { FoundationApi, InvitationCreateInput } from "@zandu/api-client";

export function useInviteMember(api: FoundationApi | undefined) {
  return useMutation({
    mutationFn: async (input: InvitationCreateInput) => {
      if (api === undefined) {
        throw new Error("Le client d’invitation est indisponible.");
      }
      return inviteMember(api, input);
    },
  });
}
