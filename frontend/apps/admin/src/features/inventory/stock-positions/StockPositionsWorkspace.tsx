"use client";

import { useState } from "react";

import { useStockPositions, useStockProducts } from "../hooks/useStockPositions";

import { StockPositionFiltersForm } from "./StockPositionFiltersForm";
import { StockPositionList } from "./StockPositionList";

import type { FoundationApi, ProductFilters, ProductResource } from "@zandu/api-client";
import type { EffectiveAccess } from "@zandu/authorization";

export function StockPositionsWorkspace({
  access,
  api,
  locale,
  organizationId,
  storeId,
}: Readonly<{
  access: EffectiveAccess | undefined;
  api: FoundationApi | undefined;
  locale: string;
  organizationId: string | undefined;
  storeId: string | undefined;
}>) {
  const [filters, setFilters] = useState<ProductFilters>({});
  const stocks = useStockPositions({ access, api, organizationId, storeId });
  const products = useStockProducts({ access, api, filters, organizationId, storeId });
  const productsById = new Map<string, ProductResource>(
    (products.query.data ?? []).map((product) => [product.id, product]),
  );
  const filtering = Object.keys(filters).length > 0;
  const rows = stocks.data
    ?.filter((stock) => !products.allowed || !filtering || productsById.has(stock.productId))
    .map((stock) => {
      const product = productsById.get(stock.productId);
      return { ...(product === undefined ? {} : { product }), stock };
    });
  const error = stocks.error ?? (products.allowed ? products.query.error : undefined);

  return (
    <>
      <StockPositionFiltersForm
        metadataAllowed={products.allowed}
        onApply={setFilters}
        onReset={() => setFilters({})}
      />
      <StockPositionList
        {...(rows === undefined ? {} : { rows })}
        {...(error === null || error === undefined ? {} : { error })}
        isLoading={stocks.isLoading || (products.allowed && products.query.isLoading)}
        locale={locale}
        onRetry={() => {
          void stocks.refetch();
          if (products.allowed) void products.query.refetch();
        }}
      />
    </>
  );
}
