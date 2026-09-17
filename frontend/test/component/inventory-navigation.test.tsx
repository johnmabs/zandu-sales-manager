"use client";

import { cleanup, render, screen, within } from "@testing-library/react";
import React from "react";
import { afterEach, describe, expect, it, vi } from "vitest";

import { InventorySection } from "../../apps/admin/src/features/inventory/components/InventorySection";
import { EffectiveAccessProvider } from "../../packages/authorization/src/index";
import { OrganizationContextProvider } from "../../packages/organization-context/src/index";

const state = vi.hoisted(() => ({
  pathname: "/admin/inventory/positions",
  storeState: {
    activeStore: {
      currency: "XAF",
      id: "store-1",
      locale: "fr_CG",
      name: "Centre",
      organizationId: "org-1",
      status: "ACTIVE" as const,
      timeZone: "Africa/Lagos",
    },
    organizationId: "org-1",
    status: "ACTIVE" as const,
    stores: [],
  },
}));

vi.mock("../../apps/admin/node_modules/next/navigation.js", () => ({
  usePathname: () => state.pathname,
}));
vi.mock("../../apps/admin/src/runtime/AdminRuntime", () => ({
  useAdminRuntime: () => ({ storeState: state.storeState }),
}));

afterEach(() => {
  cleanup();
  state.pathname = "/admin/inventory/positions";
  state.storeState = activeStoreState();
});

function renderInventory(
  permissions: string[],
  permission?: string,
  organizationId = "org-1",
  accessibleStoreIds = ["store-1"],
) {
  return render(
    <OrganizationContextProvider
      state={{ activeOrganizationId: "org-1", organizations: [], status: "ACTIVE" }}
    >
      <EffectiveAccessProvider
        access={{
          accessibleStoreIds,
          authorizationVersion: 1,
          organizationId,
          permissions,
          scope: { type: "SELECTED_STORES", storeIds: accessibleStoreIds },
        }}
      >
        <InventorySection title="Stock" {...(permission === undefined ? {} : { permission })}>
          <p>Contenu Inventory</p>
        </InventorySection>
      </EffectiveAccessProvider>
    </OrganizationContextProvider>,
  );
}

describe("Inventory navigation and store context", () => {
  it("shows only readable sections and identifies the current route", () => {
    renderInventory(["INVENTORY_READ"]);
    const navigation = screen.getByRole("navigation", { name: "Navigation Stock" });
    expect(
      within(navigation).getByRole("link", { name: "Positions" }).getAttribute("aria-current"),
    ).toBe("page");
    expect(within(navigation).getByRole("link", { name: "Valorisation" })).toBeTruthy();
    expect(within(navigation).queryByRole("link", { name: "Mouvements" })).toBeNull();
  });

  it("keeps movement, transfer and count permissions independent", () => {
    renderInventory(["STOCK_MOVEMENT_READ", "STOCK_TRANSFER_READ", "STOCK_COUNT_READ"]);
    expect(screen.getByRole("link", { name: "Mouvements" })).toBeTruthy();
    expect(screen.getByRole("link", { name: "Transferts" })).toBeTruthy();
    expect(screen.getByRole("link", { name: "Inventaires" })).toBeTruthy();
    expect(screen.queryByRole("link", { name: "Positions" })).toBeNull();
  });

  it("denies direct routes without their permission", () => {
    renderInventory(["INVENTORY_READ"], "STOCK_TRANSFER_READ");
    expect(screen.getByRole("heading", { name: "Accès refusé" })).toBeTruthy();
    expect(screen.queryByText("Contenu Inventory")).toBeNull();
  });

  it("requires an active operational store", () => {
    state.storeState = {
      organizationId: "org-1",
      status: "SELECTION_REQUIRED",
      stores: [],
    };
    renderInventory(["INVENTORY_READ"]);
    expect(screen.getByRole("heading", { name: "Magasin requis" })).toBeTruthy();
    expect(screen.getByText(/Sélectionnez un magasin opérationnel/)).toBeTruthy();
  });

  it("rejects an active store outside the effective access scope", () => {
    renderInventory(["INVENTORY_READ"], undefined, "org-1", ["store-2"]);
    expect(screen.getByRole("heading", { name: "Accès refusé" })).toBeTruthy();
  });
});

function activeStoreState() {
  return {
    activeStore: {
      currency: "XAF",
      id: "store-1",
      locale: "fr_CG",
      name: "Centre",
      organizationId: "org-1",
      status: "ACTIVE" as const,
      timeZone: "Africa/Lagos",
    },
    organizationId: "org-1",
    status: "ACTIVE" as const,
    stores: [],
  };
}
