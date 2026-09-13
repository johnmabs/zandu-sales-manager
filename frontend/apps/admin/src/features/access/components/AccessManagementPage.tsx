import { AccessNavigation } from "./AccessNavigation";

import type { ReactNode } from "react";

type AccessManagementPageProperties = Readonly<{
  children?: ReactNode;
  title: string;
}>;

/**
 * Shared F2.2 screen frame. Data and actions remain in their later feature
 * slices; this component only exposes routes the server projection permits.
 */
export function AccessManagementPage({ children, title }: AccessManagementPageProperties) {
  return (
    <section>
      <h1>{title}</h1>
      <AccessNavigation />
      {children}
    </section>
  );
}
