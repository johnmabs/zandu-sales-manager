"use client";

import { can, useEffectiveAccess } from "@zandu/authorization";
import { useOrganizationContext } from "@zandu/organization-context";
import { ErrorState, Spinner } from "@zandu/ui";
import Link from "next/link";
import { usePathname } from "next/navigation";

import { useAdminRuntime } from "../../../runtime/AdminRuntime";
import { visibleInventoryNavigation } from "../inventory-navigation";

import type { ReactNode } from "react";

export function InventorySection({
  children,
  permission,
  title,
}: Readonly<{ children?: ReactNode; permission?: string; title: string }>) {
  const access = useEffectiveAccess();
  const { activeOrganizationId } = useOrganizationContext();
  const { storeState } = useAdminRuntime();
  const pathname = usePathname();

  if (
    access === undefined ||
    activeOrganizationId === undefined ||
    storeState === undefined ||
    storeState.status === "UNKNOWN"
  ) {
    return <Spinner label="Chargement du contexte Inventory" />;
  }

  if (access.organizationId !== activeOrganizationId) {
    return <InventoryAccessDenied />;
  }

  if (storeState.status !== "ACTIVE") {
    return (
      <ErrorState title="Magasin requis" description={storeRequirementMessage(storeState.status)} />
    );
  }

  if (storeState.activeStore === undefined) {
    return (
      <ErrorState
        title="Magasin requis"
        description="Sélectionnez un magasin opérationnel dans le contexte Admin."
      />
    );
  }

  const storeId = storeState.activeStore.id;
  if (
    storeState.activeStore.organizationId !== activeOrganizationId ||
    !access.accessibleStoreIds.includes(storeId)
  ) {
    return <InventoryAccessDenied />;
  }

  const navigation = visibleInventoryNavigation(access, activeOrganizationId, storeId);
  if (
    permission === undefined
      ? navigation.length === 0
      : !can(access, permission, { organizationId: activeOrganizationId, storeId })
  ) {
    return <InventoryAccessDenied />;
  }

  return (
    <section>
      <h1>{title}</h1>
      <p>Magasin actif : {storeState.activeStore.name}</p>
      <nav aria-label="Navigation Stock">
        <ul>
          {navigation.map((item) => (
            <li key={item.href}>
              <Link
                href={item.href}
                aria-current={
                  pathname === item.href || pathname.startsWith(`${item.href}/`)
                    ? "page"
                    : undefined
                }
              >
                {item.label}
              </Link>
            </li>
          ))}
        </ul>
      </nav>
      {children}
    </section>
  );
}

function InventoryAccessDenied() {
  return (
    <ErrorState
      title="Accès refusé"
      description="Votre accès ne permet pas de consulter cette section Inventory pour le magasin actif."
    />
  );
}

function storeRequirementMessage(
  status: "NO_ACCESSIBLE_STORES" | "NO_SELECTABLE_STORES" | "SELECTION_REQUIRED",
) {
  switch (status) {
    case "NO_ACCESSIBLE_STORES":
      return "Aucun magasin n’est accessible dans cette organisation.";
    case "NO_SELECTABLE_STORES":
      return "Aucun magasin opérationnel n’est disponible.";
    case "SELECTION_REQUIRED":
      return "Sélectionnez un magasin opérationnel dans le contexte Admin.";
  }
}
