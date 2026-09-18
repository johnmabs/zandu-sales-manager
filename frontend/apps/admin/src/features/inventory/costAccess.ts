import { can } from "@zandu/authorization";

import type { EffectiveAccess } from "@zandu/authorization";

export type InventoryCostCapability = "assign" | "initialize" | "read";

const inventoryCostPermissions: Readonly<Record<InventoryCostCapability, string>> = {
  assign: "INVENTORY_COST_ASSIGN",
  initialize: "INVENTORY_COSTING_INITIALIZE",
  read: "INVENTORY_READ",
};

/**
 * Cost visibility follows the current server contract. In particular, the
 * frontend must not introduce speculative INVENTORY_COST_VIEW or
 * INVENTORY_VALUE_VIEW permissions.
 */
export function canAccessInventoryCost(
  access: EffectiveAccess | undefined,
  capability: InventoryCostCapability,
  organizationId: string | undefined,
  storeId: string | undefined,
): boolean {
  return (
    organizationId !== undefined &&
    storeId !== undefined &&
    can(access, inventoryCostPermissions[capability], { organizationId, storeId })
  );
}

export const inventoryCostReadPermission = inventoryCostPermissions.read;
