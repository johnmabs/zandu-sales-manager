import type { FoundationApi, MembershipResource } from "@zandu/api-client";

type MemberAccess = Parameters<FoundationApi["listOrganizationMembers"]>[0];

/** The API remains authoritative; the client applies a defensive tenant projection. */
export function listMembers(
  api: FoundationApi,
  access: MemberAccess,
): Promise<readonly MembershipResource[]> {
  return api.listOrganizationMembers(access);
}
