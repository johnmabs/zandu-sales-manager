import Link from "next/link";

import { AccessManagementPage } from "../components/AccessManagementPage";

/** Feature-owned invitation navigation target; invitation data starts in F2.11. */
export function InvitationsRoute() {
  return (
    <AccessManagementPage title="Invitations">
      <Link href="/app/access/invite">Inviter un membre</Link>
    </AccessManagementPage>
  );
}
