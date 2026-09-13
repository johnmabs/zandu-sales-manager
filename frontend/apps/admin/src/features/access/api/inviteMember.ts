import type {
  CreatedInvitationResource,
  FoundationApi,
  InvitationCreateInput,
} from "@zandu/api-client";

export function inviteMember(
  api: FoundationApi,
  input: InvitationCreateInput,
): Promise<CreatedInvitationResource> {
  return api.inviteMember(input);
}
