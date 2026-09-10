"use client";

import { EffectiveAccessProvider } from "@zandu/authorization";
import { OrganizationContextProvider } from "@zandu/organization-context";
import { ServerStateProvider } from "@zandu/server-state";
import { ErrorState, Spinner } from "@zandu/ui";
import { usePathname, useRouter } from "next/navigation";
import { useEffect } from "react";

import { AdminShell } from "../components/admin-shell/AdminShell";
import { ProtectedAdminShell } from "../components/admin-shell/ProtectedAdminShell";

import { useAdminRuntime } from "./AdminRuntime";

import type { ReactNode } from "react";

export function AdminApplicationShell({ children }: Readonly<{ children: ReactNode }>) {
  const runtime = useAdminRuntime();
  const pathname = usePathname();
  const router = useRouter();

  useEffect(() => {
    if (runtime.authState.status === "UNAUTHENTICATED") {
      router.replace("/login");
    }
  }, [router, runtime.authState.status]);

  if (runtime.configurationError !== undefined) {
    return <ErrorState description={runtime.configurationError} title="Configuration invalide" />;
  }

  if (runtime.authState.status !== "AUTHENTICATED" || runtime.authState.actor === undefined) {
    return <ProtectedAdminShell authState={runtime.authState}>{children}</ProtectedAdminShell>;
  }

  if (runtime.contextStatus === "LOADING" || runtime.contextStatus === "IDLE") {
    return <Spinner label="Chargement du contexte de travail" />;
  }
  if (runtime.contextStatus === "ERROR") {
    return (
      <ErrorState
        description={runtime.contextError ?? "Le contexte de travail est indisponible."}
        title="Contexte indisponible"
      />
    );
  }

  const actor = runtime.authState.actor;
  const activeOrganization = runtime.organizationState.activeOrganization;
  if (activeOrganization === undefined || runtime.storeState === undefined) {
    return (
      <ErrorState description="Sélectionnez une organisation valide." title="Contexte requis" />
    );
  }

  return (
    <ServerStateProvider client={runtime.queryClient}>
      <OrganizationContextProvider state={runtime.organizationState}>
        <EffectiveAccessProvider access={actor.effectiveAccess}>
          <AdminShell
            access={actor.effectiveAccess}
            activeOrganization={activeOrganization}
            {...(runtime.storeState.activeStore === undefined
              ? {}
              : { activeStore: runtime.storeState.activeStore })}
            actorLabel={actor.email ?? actor.userId}
            onLogout={() =>
              void runtime
                .logout()
                .catch(() => undefined)
                .then(() => router.replace("/login"))
            }
            onOrganizationChange={(organizationId) =>
              void runtime.selectOrganization(organizationId)
            }
            onStoreChange={runtime.selectStore}
            organizations={runtime.organizationState.organizations}
            pathname={pathname}
            storeContextStatus={runtime.storeState.status}
            stores={runtime.storeState.stores.filter((store) => store.status === "ACTIVE")}
          >
            {children}
          </AdminShell>
        </EffectiveAccessProvider>
      </OrganizationContextProvider>
    </ServerStateProvider>
  );
}
