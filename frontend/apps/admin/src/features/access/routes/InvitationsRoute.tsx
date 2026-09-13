import { AccessManagementPage } from "../components/AccessManagementPage";
import { InvitationLifecycleUnavailable } from "../invitations/InvitationLifecycleUnavailable";

/** The lifecycle view follows only operations currently published by OpenAPI. */
export function InvitationsRoute() {
  return (
    <AccessManagementPage title="Invitations">
      <InvitationLifecycleUnavailable />
    </AccessManagementPage>
  );
}
