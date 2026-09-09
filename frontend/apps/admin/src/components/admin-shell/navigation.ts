import { canViewNavigation } from "@zandu/authorization";

import type { EffectiveAccess, NavigationItem } from "@zandu/authorization";

export type AdminNavigationItem = NavigationItem &
  Readonly<{
    href: string;
    label: string;
  }>;

export const adminNavigation: readonly AdminNavigationItem[] = [
  {
    capabilities: [{ permission: "ORGANIZATION_READ" }],
    href: "/app/organization",
    label: "Organisation",
  },
  {
    capabilities: [{ permission: "STORE_READ" }],
    href: "/app/stores",
    label: "Magasins",
  },
  {
    capabilities: [{ permission: "MEMBER_READ" }],
    href: "/app/members",
    label: "Membres",
  },
  {
    capabilities: [{ permission: "CATALOG_READ" }, { permission: "PRODUCT_READ" }],
    href: "/app/catalog",
    label: "Catalogue",
  },
  {
    capabilities: [{ permission: "PRICE_LIST_READ" }, { permission: "PRODUCT_PRICE_READ" }],
    href: "/app/pricing",
    label: "Tarification",
  },
  {
    capabilities: [{ permission: "INVENTORY_READ" }],
    href: "/app/inventory",
    label: "Stock",
  },
  {
    capabilities: [{ permission: "SUPPLIER_READ" }, { permission: "PURCHASE_ORDER_READ" }],
    href: "/app/purchasing",
    label: "Approvisionnements",
  },
  {
    capabilities: [{ permission: "CASH_REGISTER_READ" }, { permission: "CASH_SESSION_READ" }],
    href: "/app/cash",
    label: "Caisse",
  },
  {
    capabilities: [{ permission: "SALE_READ" }],
    href: "/app/sales",
    label: "Ventes",
  },
];

/** Navigation is an UX projection only; direct routes remain server-authorized. */
export function visibleAdminNavigation(
  access: EffectiveAccess | undefined,
): readonly AdminNavigationItem[] {
  return adminNavigation.filter((item) => canViewNavigation(access, item));
}
