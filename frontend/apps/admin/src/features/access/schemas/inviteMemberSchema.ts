import { z } from "@zandu/forms";

import type { InvitationCreateInput } from "@zandu/api-client";

export const inviteMemberSchema = z
  .object({
    email: z.string().email("Saisissez une adresse email valide."),
    expiresAt: z.string(),
    roleCodes: z.array(z.string()).min(1, "Sélectionnez au moins un rôle."),
    scopeType: z.enum(["ORGANIZATION", "SELECTED_STORES"]),
    storeIds: z.array(z.string()),
  })
  .superRefine((values, context) => {
    if (values.scopeType === "SELECTED_STORES" && values.storeIds.length === 0) {
      context.addIssue({
        code: z.ZodIssueCode.custom,
        message: "Sélectionnez au moins un magasin.",
        path: ["storeIds"],
      });
    }
  });

export type InviteMemberFormValues = z.infer<typeof inviteMemberSchema>;

export function toInvitationCreateInput(values: InviteMemberFormValues): InvitationCreateInput {
  const storeIds = values.scopeType === "SELECTED_STORES" ? values.storeIds : [];

  return {
    email: values.email.trim(),
    expiresAt: values.expiresAt === "" ? null : new Date(values.expiresAt).toISOString(),
    roleAssignments: values.roleCodes.map((roleCode) => ({
      roleCode: roleCode.trim(),
      storeIds,
    })),
  };
}
