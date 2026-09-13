import { AccessManagementPage } from "../components/AccessManagementPage";
import { InviteMemberPage } from "../invitations/InviteMemberPage";

export function InviteMemberRoute() {
  return (
    <AccessManagementPage title="Inviter un membre">
      <InviteMemberPage />
    </AccessManagementPage>
  );
}
