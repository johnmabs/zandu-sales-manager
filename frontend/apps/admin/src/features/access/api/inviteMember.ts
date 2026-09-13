import type { FoundationApi, InvitationCreateInput } from "@zandu/api-client";

export function inviteMember(api: FoundationApi, input: InvitationCreateInput): Promise<void> {
  return api.inviteMember(input);
}
