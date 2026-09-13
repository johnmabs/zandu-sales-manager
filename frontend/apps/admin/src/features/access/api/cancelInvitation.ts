import type { FoundationApi } from "@zandu/api-client";

export async function cancelInvitation(api: FoundationApi, invitationId: string) {
  return api.cancelInvitation(invitationId);
}
