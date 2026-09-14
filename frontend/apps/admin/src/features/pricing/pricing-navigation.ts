import { canViewNavigation } from "@zandu/authorization";

import type { EffectiveAccess } from "@zandu/authorization";

export const pricingNavigation = [
  {
    href: "/admin/pricing/price-lists",
    label: "Listes de prix",
    capabilities: [{ permission: "PRICE_LIST_READ" }],
  },
  {
    href: "/admin/pricing/product-prices",
    label: "Prix produits",
    capabilities: [{ permission: "PRODUCT_PRICE_READ" }],
  },
] as const;

export function visiblePricingNavigation(access: EffectiveAccess | undefined) {
  return pricingNavigation.filter((item) => canViewNavigation(access, item));
}
