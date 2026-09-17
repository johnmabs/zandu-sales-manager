import type { ProductFilters } from "@zandu/api-client";

/** Canonical transport values only; Catalog owns matching semantics. */
export function normalizeStockPositionFilters(filters: ProductFilters): ProductFilters {
  const normalized: { search?: string; status?: string; type?: string } = {};
  for (const key of ["search", "status", "type"] as const) {
    const value = filters[key]?.trim();
    if (value !== undefined && value !== "") normalized[key] = value;
  }
  return normalized;
}
