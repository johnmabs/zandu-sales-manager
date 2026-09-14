"use client";
import { useEffectiveAccess } from "@zandu/authorization";
import { useOrganizationContext } from "@zandu/organization-context";

import { useAdminRuntime } from "../../../runtime/AdminRuntime";
import { useProductList } from "../hooks/useProductList";

import { ProductList } from "./ProductList";

export function ProductListPage() {
  const access = useEffectiveAccess();
  const { activeOrganizationId } = useOrganizationContext();
  const { api } = useAdminRuntime();
  const products = useProductList({ api, access, organizationId: activeOrganizationId });
  return (
    <ProductList
      products={products.data}
      isLoading={products.isLoading}
      error={products.error}
      onRetry={() => void products.refetch()}
    />
  );
}
