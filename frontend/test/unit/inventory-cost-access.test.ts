import { describe, expect, it } from "vitest";

import { canAccessInventoryCost } from "../../apps/admin/src/features/inventory/costAccess";

import type { EffectiveAccess } from "../../packages/authorization/src/index";

const access: EffectiveAccess = {
  accessibleStoreIds: ["store-1"],
  authorizationVersion: 1,
  organizationId: "org-1",
  permissions: ["INVENTORY_READ", "INVENTORY_COSTING_INITIALIZE", "INVENTORY_COST_ASSIGN"],
  scope: { storeIds: ["store-1"], type: "SELECTED_STORES" },
};

describe("inventory cost access", () => {
  it("maps each cost capability to the permission published by the server", () => {
    expect(canAccessInventoryCost(access, "read", "org-1", "store-1")).toBe(true);
    expect(canAccessInventoryCost(access, "initialize", "org-1", "store-1")).toBe(true);
    expect(canAccessInventoryCost(access, "assign", "org-1", "store-1")).toBe(true);
  });

  it("does not treat operation or speculative visibility permissions as read access", () => {
    for (const permission of [
      "INVENTORY_COSTING_INITIALIZE",
      "INVENTORY_COST_ASSIGN",
      "INVENTORY_COST_VIEW",
      "INVENTORY_VALUE_VIEW",
    ]) {
      expect(
        canAccessInventoryCost(
          { ...access, permissions: [permission] },
          "read",
          "org-1",
          "store-1",
        ),
      ).toBe(false);
    }
  });

  it("fails closed outside the effective organization and Store scope", () => {
    expect(canAccessInventoryCost(access, "read", "org-2", "store-1")).toBe(false);
    expect(canAccessInventoryCost(access, "read", "org-1", "store-2")).toBe(false);
    expect(canAccessInventoryCost(access, "read", undefined, "store-1")).toBe(false);
    expect(canAccessInventoryCost(access, "read", "org-1", undefined)).toBe(false);
  });
});
