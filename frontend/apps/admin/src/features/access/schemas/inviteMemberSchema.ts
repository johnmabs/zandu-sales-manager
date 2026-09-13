import { z } from "@zandu/forms";

import type { InvitationCreateInput } from "@zandu/api-client";

export const inviteMemberSchema = z.object({
  email: z.string().email("Saisissez une adresse email valide."),
  expiresAt: z.string(),
  roleCode: z.string().min(1, "Saisissez un code de rôle."),
});

export type InviteMemberFormValues = z.infer<typeof inviteMemberSchema>;

export function toInvitationCreateInput(values: InviteMemberFormValues): InvitationCreateInput {
  return {
    email: values.email.trim(),
    expiresAt: values.expiresAt === "" ? null : new Date(values.expiresAt).toISOString(),
    roleAssignments: [{ roleCode: values.roleCode.trim(), storeIds: [] }],
  };
}
