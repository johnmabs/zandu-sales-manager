import { AccessManagementPage } from "../components/AccessManagementPage";
import { MemberListPage } from "../members/MemberListPage";

/** Feature-owned route composition kept independent from the Next.js adapter. */
export function MembersRoute() {
  return (
    <AccessManagementPage title="Membres">
      <MemberListPage />
    </AccessManagementPage>
  );
}
