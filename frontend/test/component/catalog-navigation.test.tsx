"use client";
import { cleanup, render, screen, within } from "@testing-library/react";
import React from "react";
import { afterEach, describe, expect, it, vi } from "vitest";

import { CatalogSection } from "../../apps/admin/src/features/catalog/components/CatalogSection";
import { PricingSection } from "../../apps/admin/src/features/pricing/components/PricingSection";
import { EffectiveAccessProvider } from "../../packages/authorization/src/index";
import { OrganizationContextProvider } from "../../packages/organization-context/src/index";

vi.mock("../../apps/admin/node_modules/next/navigation.js", () => ({
  usePathname: () => "/admin/catalog/products",
}));
afterEach(cleanup);
function renderSection(
  permissions: string[],
  section: "catalog" | "pricing" = "catalog",
  organizationId = "org-1",
  permission?: string,
) {
  return render(
    <OrganizationContextProvider
      state={{ activeOrganizationId: "org-1", organizations: [], status: "ACTIVE" }}
    >
      <EffectiveAccessProvider
        access={{
          organizationId,
          authorizationVersion: 1,
          permissions,
          accessibleStoreIds: [],
          scope: { type: "ORGANIZATION" },
        }}
      >
        {section === "catalog" ? (
          <CatalogSection title="Catalogue" {...(permission === undefined ? {} : { permission })}>
            <p>Contenu protégé</p>
          </CatalogSection>
        ) : (
          <PricingSection title="Tarification" />
        )}
      </EffectiveAccessProvider>
    </OrganizationContextProvider>,
  );
}
describe("Catalog and Pricing navigation", () => {
  it("links only readable catalog sections and identifies the current page", () => {
    renderSection(["PRODUCT_READ"]);
    const nav = screen.getByRole("navigation", { name: "Navigation Catalogue" });
    expect(within(nav).getByRole("link", { name: "Produits" }).getAttribute("aria-current")).toBe(
      "page",
    );
    expect(within(nav).queryByRole("link", { name: "Catégories" })).toBeNull();
  });
  it("uses CATALOG_READ for categories", () => {
    renderSection(["CATALOG_READ"]);
    expect(screen.getByRole("link", { name: "Catégories" }).getAttribute("href")).toBe(
      "/admin/catalog/categories",
    );
    expect(screen.queryByRole("link", { name: "Produits" })).toBeNull();
  });
  it("uses independent Pricing read permissions", () => {
    renderSection(["PRODUCT_PRICE_READ"], "pricing");
    expect(screen.getByRole("link", { name: "Prix produits" })).toBeTruthy();
    expect(screen.queryByRole("link", { name: "Listes de prix" })).toBeNull();
  });
  it("denies a direct route without its permission", () => {
    renderSection(["CATALOG_READ"], "catalog", "org-1", "PRODUCT_READ");
    expect(screen.getByRole("heading", { name: "Accès refusé" })).toBeTruthy();
    expect(screen.queryByText("Contenu protégé")).toBeNull();
  });
  it("denies a mismatched organization", () => {
    renderSection(["PRODUCT_READ"], "catalog", "org-2");
    expect(screen.getByRole("heading", { name: "Accès refusé" })).toBeTruthy();
  });
});
