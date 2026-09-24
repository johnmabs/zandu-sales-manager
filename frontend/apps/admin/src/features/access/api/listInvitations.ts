import type { FoundationApi, InvitationResource } from "@zandu/api-client";

type InvitationAccess = Parameters<FoundationApi["listOrganizationInvitations"]>[0];

/** The API is authoritative; the client keeps a defensive tenant projection. */
export function listInvitations(
  api: FoundationApi,
  access: InvitationAccess,
): Promise<readonly InvitationResource[]> {
  return api.listOrganizationInvitations(access);
}
