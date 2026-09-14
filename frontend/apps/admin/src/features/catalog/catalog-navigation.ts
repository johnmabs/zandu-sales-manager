import { canViewNavigation } from "@zandu/authorization";

import type { EffectiveAccess } from "@zandu/authorization";

export const catalogNavigation = [
  {
    href: "/admin/catalog/products",
    label: "Produits",
    capabilities: [{ permission: "PRODUCT_READ" }],
  },
  {
    href: "/admin/catalog/categories",
    label: "Catégories",
    capabilities: [{ permission: "CATALOG_READ" }],
  },
] as const;

export function visibleCatalogNavigation(access: EffectiveAccess | undefined) {
  return catalogNavigation.filter((item) => canViewNavigation(access, item));
}
