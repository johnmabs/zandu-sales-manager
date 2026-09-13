import { AccessManagementPage } from "../components/AccessManagementPage";
import { RoleCatalogPage } from "../roles/RoleCatalogPage";

/** Feature-owned role navigation target; the catalog starts in F2.5. */
export function RolesRoute() {
  return (
    <AccessManagementPage title="Rôles">
      <RoleCatalogPage />
    </AccessManagementPage>
  );
}
