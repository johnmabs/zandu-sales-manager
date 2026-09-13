import { AccessManagementPage } from "../components/AccessManagementPage";

/** Feature-owned route composition kept independent from the Next.js adapter. */
export function MembersRoute() {
  return <AccessManagementPage title="Membres" />;
}
