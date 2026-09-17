import { can } from "@zandu/authorization";

import type { EffectiveAccess } from "@zandu/authorization";

export const inventoryNavigation = [
  {
    href: "/admin/inventory/positions",
    label: "Positions",
    permission: "INVENTORY_READ",
  },
  {
    href: "/admin/inventory/movements",
    label: "Mouvements",
    permission: "STOCK_MOVEMENT_READ",
  },
  {
    href: "/admin/inventory/valuations",
    label: "Valorisation",
    permission: "INVENTORY_READ",
  },
  {
    href: "/admin/inventory/transfers",
    label: "Transferts",
    permission: "STOCK_TRANSFER_READ",
  },
  {
    href: "/admin/inventory/counts",
    label: "Inventaires",
    permission: "STOCK_COUNT_READ",
  },
] as const;

export function visibleInventoryNavigation(
  access: EffectiveAccess | undefined,
  organizationId: string | undefined,
  storeId: string | undefined,
) {
  if (organizationId === undefined || storeId === undefined) return [];
  return inventoryNavigation.filter((item) =>
    can(access, item.permission, { organizationId, storeId }),
  );
}
