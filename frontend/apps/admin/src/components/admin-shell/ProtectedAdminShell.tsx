"use client";

import { ErrorState, Spinner } from "@zandu/ui";

import type { AuthState } from "@zandu/auth";
import type { ReactNode } from "react";

export function ProtectedAdminShell({
  authState,
  children,
}: Readonly<{
  authState: AuthState;
  children: ReactNode;
}>) {
  if (authState.status === "UNKNOWN" || authState.status === "AUTHENTICATING") {
    return (
      <main className="zandu-admin-session-state">
        <Spinner label="Chargement de la session" />
      </main>
    );
  }

  if (authState.status === "REFRESHING") {
    return (
      <main className="zandu-admin-session-state">
        <Spinner label="Actualisation de la session" />
      </main>
    );
  }

  if (authState.status === "UNAUTHENTICATED") {
    return (
      <main className="zandu-admin-session-state">
        <ErrorState
          description="Connectez-vous pour accéder à l’administration."
          title="Session requise"
        />
      </main>
    );
  }

  return <>{children}</>;
}
