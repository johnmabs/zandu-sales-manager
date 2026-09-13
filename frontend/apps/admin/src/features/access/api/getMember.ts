import type { FoundationApi, MembershipResource } from "@zandu/api-client";

type MemberAccess = Parameters<FoundationApi["getOrganizationMember"]>[1];

/** Keeps an unexpected out-of-organization response indistinguishable from absence. */
export function getMember(
  api: FoundationApi,
  membershipId: string,
  access: MemberAccess,
): Promise<MembershipResource | undefined> {
  return api.getOrganizationMember(membershipId, access);
}
