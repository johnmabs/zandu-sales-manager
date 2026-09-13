import type { FoundationApi, RoleAssignmentCreateInput } from "@zandu/api-client";

export async function assignMemberRole(
  api: FoundationApi,
  membershipId: string,
  input: RoleAssignmentCreateInput,
) {
  return api.assignMemberRole(membershipId, input);
}
