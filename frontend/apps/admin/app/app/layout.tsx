import { ProtectedAdminShell } from "../../src/shell/ProtectedAdminShell";

import type { ReactNode } from "react";

type AdminLayoutProperties = Readonly<{
  children: ReactNode;
}>;

/**
 * The runtime session bridge supplies the authenticated AdminShell later.
 * Until it has resolved, protected route content must remain undisclosed.
 */
export default function AdminLayout({ children }: AdminLayoutProperties) {
  return <ProtectedAdminShell authState={{ status: "UNKNOWN" }}>{children}</ProtectedAdminShell>;
}
