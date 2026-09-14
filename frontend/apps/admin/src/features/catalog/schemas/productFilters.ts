import type { ProductFilters } from "@zandu/api-client";

/** Canonical query values only; Symfony owns matching and validation. */
export function normalizeProductFilters(filters: ProductFilters): ProductFilters {
  const result: {
    status?: string;
    type?: string;
    categoryId?: string;
    productCode?: string;
    search?: string;
  } = {};
  for (const key of ["search", "status", "type", "categoryId", "productCode"] as const) {
    const value = filters[key]?.trim();
    if (value !== undefined && value !== "") result[key] = value;
  }
  return result;
}
