import type { EffectiveAccess } from "@zandu/authorization";

export type RoleAccessState = "ALLOWED" | "DENIED" | "OUT_OF_SCOPE" | "UNRESOLVED";

export function resolveRoleReadAccess(
  access: EffectiveAccess | undefined,
  organizationId: string | undefined,
): RoleAccessState {
  if (access === undefined || organizationId === undefined) {
    return "UNRESOLVED";
  }
  if (access.organizationId !== organizationId) {
    return "OUT_OF_SCOPE";
  }

  return access.permissions.includes("ROLE_READ") ? "ALLOWED" : "DENIED";
}
