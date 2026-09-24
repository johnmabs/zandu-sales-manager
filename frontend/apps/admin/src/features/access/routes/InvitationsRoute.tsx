import { AccessManagementPage } from "../components/AccessManagementPage";
import { InvitationListPage } from "../invitations/InvitationListPage";

export function InvitationsRoute() {
  return (
    <AccessManagementPage title="Invitations">
      <InvitationListPage />
    </AccessManagementPage>
  );
}
