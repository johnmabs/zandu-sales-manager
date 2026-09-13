"use client";

import { useEffectiveAccess } from "@zandu/authorization";

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
  const access = useEffectiveAccess();
  return (
    <section>
      <h1>{title}</h1>
      {access?.scope.type === "SELECTED_STORES" ? (
        <p role="status">
          Votre administration est limitée à {access.accessibleStoreIds.length} magasin(s) :{" "}
          {access.accessibleStoreIds.join(", ")}.
        </p>
      ) : (
        <p>Portée d’administration : organisation entière.</p>
      )}
      <AccessNavigation />
      {children}
    </section>
  );
}
