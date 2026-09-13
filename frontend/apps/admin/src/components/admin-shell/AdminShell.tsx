"use client";

import { NotificationViewport } from "@zandu/notifications/react";
import { Button, DropdownMenu, Select } from "@zandu/ui";
import Link from "next/link";

import { breadcrumbsForPath } from "./breadcrumbs";
import { visibleAdminNavigation } from "./navigation";

import type { EffectiveAccess } from "@zandu/authorization";
import type { AccessibleStore, StoreContextStatus } from "@zandu/store-context";
import type { ReactNode } from "react";

export type OrganizationOption = Readonly<{
  id: string;
  name: string;
}>;

export type AdminShellProps = Readonly<{
  access: EffectiveAccess;
  activeOrganization: OrganizationOption;
  activeStore?: AccessibleStore;
  actorLabel: string;
  children: ReactNode;
  onOrganizationChange: (organizationId: string) => void;
  onLogout: () => void;
  onStoreChange: (storeId: string) => void;
  organizations: readonly OrganizationOption[];
  pathname: string;
  storeContextStatus: StoreContextStatus;
  stores: readonly AccessibleStore[];
}>;

/**
 * Desktop-first frame for an already-resolved, authenticated Admin session.
 * It deliberately receives server-derived context instead of owning it.
 */
export function AdminShell({
  access,
  activeOrganization,
  activeStore,
  actorLabel,
  children,
  onLogout,
  onOrganizationChange,
  onStoreChange,
  organizations,
  pathname,
  storeContextStatus,
  stores,
}: AdminShellProps) {
  const navigation = visibleAdminNavigation(access);
  const breadcrumbs = breadcrumbsForPath(pathname, navigation);
  const storeContextMessage = storeContextMessageForStatus(storeContextStatus);

  return (
    <div className="zandu-admin-shell">
      <a className="zandu-admin-shell__skip-link" href="#admin-content">
        Aller au contenu
      </a>
      <aside className="zandu-admin-shell__sidebar">
        <Link className="zandu-admin-shell__brand" href="/app">
          Zandu Admin
        </Link>
        <nav aria-label="Navigation principale">
          <ul className="zandu-admin-shell__navigation-list">
            {navigation.map((item) => (
              <li key={item.href}>
                <Link
                  aria-current={
                    pathname === item.href || pathname.startsWith(`${item.href}/`)
                      ? "page"
                      : undefined
                  }
                  href={item.href}
                >
                  {item.label}
                </Link>
              </li>
            ))}
          </ul>
        </nav>
      </aside>
      <div className="zandu-admin-shell__workspace">
        <header className="zandu-admin-shell__header">
          <div className="zandu-admin-shell__selectors">
            <label>
              Organisation
              <Select
                aria-label="Organisation active"
                onChange={(event) => onOrganizationChange(event.target.value)}
                value={activeOrganization.id}
              >
                {organizations.map((organization) => (
                  <option key={organization.id} value={organization.id}>
                    {organization.name}
                  </option>
                ))}
              </Select>
            </label>
            <label>
              Magasin
              <Select
                aria-label="Magasin actif"
                onChange={(event) => onStoreChange(event.target.value)}
                value={activeStore?.id ?? ""}
              >
                <option disabled value="">
                  Sélectionner un magasin
                </option>
                {stores.map((store) => (
                  <option key={store.id} value={store.id}>
                    {store.name}
                  </option>
                ))}
              </Select>
            </label>
          </div>
          <DropdownMenu label={actorLabel}>
            <Button onClick={onLogout} type="button" variant="secondary">
              Se déconnecter
            </Button>
          </DropdownMenu>
        </header>
        <nav aria-label="Fil d’Ariane" className="zandu-admin-shell__breadcrumbs">
          <ol>
            {breadcrumbs.map((breadcrumb, index) => (
              <li key={breadcrumb.href}>
                <Link
                  aria-current={index === breadcrumbs.length - 1 ? "page" : undefined}
                  href={breadcrumb.href}
                >
                  {breadcrumb.label}
                </Link>
              </li>
            ))}
          </ol>
        </nav>
        <NotificationViewport />
        <main className="zandu-admin-shell__content" id="admin-content">
          {storeContextMessage === undefined ? null : (
            <p className="zandu-admin-shell__context-notice" role="status">
              {storeContextMessage}
            </p>
          )}
          {children}
        </main>
      </div>
    </div>
  );
}

export function storeContextMessageForStatus(status: StoreContextStatus): string | undefined {
  switch (status) {
    case "NO_ACCESSIBLE_STORES":
      return "Aucun magasin n’est accessible dans cette organisation.";
    case "NO_SELECTABLE_STORES":
      return "Aucun magasin opérationnel n’est disponible. Réactivez un magasin pour reprendre les opérations.";
    case "SELECTION_REQUIRED":
      return "Sélectionnez un magasin opérationnel pour continuer.";
    case "UNKNOWN":
      return "Le contexte magasin n’est pas encore disponible.";
    case "ACTIVE":
      return undefined;
  }
}
