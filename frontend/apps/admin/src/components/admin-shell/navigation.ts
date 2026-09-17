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
    href: "/admin/organization",
    label: "Organisation",
  },
  {
    capabilities: [{ permission: "STORE_READ" }],
    href: "/admin/stores",
    label: "Magasins",
  },
  {
    capabilities: [
      { permission: "MEMBER_READ" },
      { permission: "MEMBER_INVITE" },
      { permission: "ROLE_READ" },
    ],
    href: "/admin/access",
    label: "Accès",
  },
  {
    capabilities: [{ permission: "CATALOG_READ" }, { permission: "PRODUCT_READ" }],
    href: "/admin/catalog",
    label: "Catalogue",
  },
  {
    capabilities: [{ permission: "PRICE_LIST_READ" }, { permission: "PRODUCT_PRICE_READ" }],
    href: "/admin/pricing",
    label: "Tarification",
  },
  {
    capabilities: [
      { permission: "INVENTORY_READ" },
      { permission: "STOCK_MOVEMENT_READ" },
      { permission: "STOCK_TRANSFER_READ" },
      { permission: "STOCK_COUNT_READ" },
    ],
    href: "/admin/inventory",
    label: "Stock",
  },
  {
    capabilities: [{ permission: "SUPPLIER_READ" }, { permission: "PURCHASE_ORDER_READ" }],
    href: "/admin/purchasing",
    label: "Approvisionnements",
  },
  {
    capabilities: [{ permission: "CASH_REGISTER_READ" }, { permission: "CASH_SESSION_READ" }],
    href: "/admin/cash",
    label: "Caisse",
  },
  {
    capabilities: [{ permission: "SALE_READ" }],
    href: "/admin/sales",
    label: "Ventes",
  },
];

/** Navigation is an UX projection only; direct routes remain server-authorized. */
export function visibleAdminNavigation(
  access: EffectiveAccess | undefined,
): readonly AdminNavigationItem[] {
  return adminNavigation.filter((item) => canViewNavigation(access, item));
}
