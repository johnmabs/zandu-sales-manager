import type { EffectiveAccess } from "@zandu/authorization";

export const memberPermissions = {
  read: "MEMBER_READ",
} as const;

export type MemberAccessState = "ALLOWED" | "DENIED" | "OUT_OF_SCOPE" | "UNRESOLVED";

export function resolveMemberAccess(
  access: EffectiveAccess | undefined,
  organizationId: string | undefined,
): MemberAccessState {
  if (access === undefined || organizationId === undefined) {
    return "UNRESOLVED";
  }
  if (access.organizationId !== organizationId) {
    return "OUT_OF_SCOPE";
  }

  return access.permissions.includes(memberPermissions.read) ? "ALLOWED" : "DENIED";
}
