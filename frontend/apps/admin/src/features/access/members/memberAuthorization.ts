import type { EffectiveAccess } from "@zandu/authorization";

export const memberPermissions = {
  read: "MEMBER_READ",
  revoke: "MEMBER_REVOKE",
  suspend: "MEMBER_SUSPEND",
  assignRole: "ROLE_ASSIGN",
  removeRole: "ROLE_REVOKE",
} as const;

export function availableMemberActions(status: string, permissions: readonly string[]) {
  return {
    assignRole: status === "ACTIVE" && permissions.includes(memberPermissions.assignRole),
    reactivate: status === "SUSPENDED" && permissions.includes(memberPermissions.suspend),
    removeRole: status === "ACTIVE" && permissions.includes(memberPermissions.removeRole),
    revoke: status !== "REVOKED" && permissions.includes(memberPermissions.revoke),
    suspend: status === "ACTIVE" && permissions.includes(memberPermissions.suspend),
  } as const;
}

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
