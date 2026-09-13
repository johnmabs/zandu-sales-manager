import { z } from "@zandu/forms";

import type { InvitationCreateInput } from "@zandu/api-client";

export const inviteMemberSchema = z.object({
  email: z.string().email("Saisissez une adresse email valide."),
  expiresAt: z.string(),
  roleCodes: z.array(z.string()).min(1, "Sélectionnez au moins un rôle."),
});

export type InviteMemberFormValues = z.infer<typeof inviteMemberSchema>;

export function toInvitationCreateInput(values: InviteMemberFormValues): InvitationCreateInput {
  return {
    email: values.email.trim(),
    expiresAt: values.expiresAt === "" ? null : new Date(values.expiresAt).toISOString(),
    roleAssignments: values.roleCodes.map((roleCode) => ({
      roleCode: roleCode.trim(),
      storeIds: [],
    })),
  };
}
