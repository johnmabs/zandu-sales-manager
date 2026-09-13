import { z } from "@zandu/forms";

import type { RoleAssignmentCreateInput } from "@zandu/api-client";

export const assignRoleSchema = z
  .object({
    expiresAt: z.string(),
    roleId: z.string().min(1, "Sélectionnez un rôle."),
    scopeType: z.enum(["ORGANIZATION", "SELECTED_STORES"]),
    storeIds: z.array(z.string()),
  })
  .superRefine((value, context) => {
    if (value.scopeType === "SELECTED_STORES" && value.storeIds.length === 0) {
      context.addIssue({
        code: z.ZodIssueCode.custom,
        message: "Sélectionnez au moins un magasin.",
        path: ["storeIds"],
      });
    }
  });

export type AssignRoleFormValues = z.infer<typeof assignRoleSchema>;

export function toRoleAssignmentInput(value: AssignRoleFormValues): RoleAssignmentCreateInput {
  return {
    expiresAt: value.expiresAt === "" ? null : new Date(value.expiresAt).toISOString(),
    roleId: value.roleId,
    scopeType: value.scopeType,
    storeIds: value.scopeType === "ORGANIZATION" ? [] : value.storeIds,
  };
}
