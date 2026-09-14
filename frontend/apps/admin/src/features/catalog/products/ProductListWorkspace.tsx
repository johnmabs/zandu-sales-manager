"use client";
import { useState } from "react";

import { useCategoryOptions } from "../hooks/useCategoryOptions";
import { useProductList } from "../hooks/useProductList";

import { ProductFiltersForm } from "./ProductFiltersForm";
import { ProductList } from "./ProductList";

import type { FoundationApi, ProductFilters } from "@zandu/api-client";
import type { EffectiveAccess } from "@zandu/authorization";

export function ProductListWorkspace({
  api,
  access,
  organizationId,
}: Readonly<{
  api: FoundationApi | undefined;
  access: EffectiveAccess | undefined;
  organizationId: string | undefined;
}>) {
  const [filters, setFilters] = useState<ProductFilters>({});
  const categories = useCategoryOptions({ api, access, organizationId });
  const products = useProductList({ api, access, organizationId, filters });
  return (
    <>
      <ProductFiltersForm
        categories={categories.data ?? []}
        categoryAllowed={categories.allowed}
        categoryLoading={categories.isLoading}
        categoryError={categories.isError}
        onRetryCategories={() => void categories.refetch()}
        onApply={setFilters}
        onReset={() => setFilters({})}
      />
      <ProductList
        products={products.data}
        isLoading={products.isLoading}
        error={products.error}
        onRetry={() => void products.refetch()}
      />
    </>
  );
}
