import { AdminApplicationShell } from "../../src/runtime/AdminApplicationShell";

import type { ReactNode } from "react";

type AdminLayoutProperties = Readonly<{
  children: ReactNode;
}>;

/**
 * Session, tenant access, store selection and tenant-aware cache are resolved
 * before protected route content is disclosed.
 */
export default function AdminLayout({ children }: AdminLayoutProperties) {
  return <AdminApplicationShell>{children}</AdminApplicationShell>;
}
