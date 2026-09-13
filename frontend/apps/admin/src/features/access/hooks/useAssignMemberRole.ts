"use client";

import { useMutation } from "@tanstack/react-query";

import { assignMemberRole } from "../api/assignMemberRole";

import type { FoundationApi, RoleAssignmentCreateInput } from "@zandu/api-client";

export function useAssignMemberRole(api: FoundationApi | undefined, membershipId: string) {
  return useMutation({
    mutationFn: async (input: RoleAssignmentCreateInput) => {
      if (api === undefined) throw new Error("Le client d’attribution est indisponible.");
      return assignMemberRole(api, membershipId, input);
    },
  });
}
