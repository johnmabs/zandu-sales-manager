"use client";

import { useQuery } from "@tanstack/react-query";
import { ApiContractError } from "@zandu/api-client";
import { can, useEffectiveAccess } from "@zandu/authorization";
import { formatMoney, formatQuantity } from "@zandu/domain-formatting";
import { useOrganizationContext } from "@zandu/organization-context";
import { queryKeys } from "@zandu/server-state";
import { EmptyState, ErrorState, Spinner } from "@zandu/ui";
import { useState } from "react";

import { useAdminRuntime } from "../../../runtime/AdminRuntime";
import {
  getInventoryValuation,
  getStock,
  listInventoryValuationMovements,
  listInventoryValuations,
  listStocks,
} from "../api/readInventory";

import { InventoryValuationList, ValuationLoadError } from "./InventoryValuationList";
import { InventoryValuationMovementList } from "./InventoryValuationMovementList";

import type { FoundationApi, InventoryValuationResource } from "@zandu/api-client";
import type { EffectiveAccess } from "@zandu/authorization";

const LEDGER_PAGE_SIZE = 25;

export function InventoryValuationsPage({ productId }: Readonly<{ productId?: string }>) {
  const access = useEffectiveAccess();
  const { activeOrganizationId } = useOrganizationContext();
  const { api, storeState } = useAdminRuntime();
  const store = storeState?.status === "ACTIVE" ? storeState.activeStore : undefined;

  return (
    <InventoryValuationsWorkspace
      key={`${activeOrganizationId}:${store?.id}:${access?.authorizationVersion}:${productId ?? "all"}`}
      access={access}
      api={api}
      currency={store?.currency ?? "XAF"}
      locale={store?.locale ?? "fr-FR"}
      organizationId={activeOrganizationId}
      {...(productId === undefined ? {} : { productId })}
      storeId={store?.id}
      timeZone={store?.timeZone ?? "UTC"}
    />
  );
}

export function InventoryValuationsWorkspace({
  access,
  api,
  currency,
  locale,
  organizationId,
  productId,
  storeId,
  timeZone,
}: Readonly<{
  access: EffectiveAccess | undefined;
  api: FoundationApi | undefined;
  currency: string;
  locale: string;
  organizationId: string | undefined;
  productId?: string;
  storeId: string | undefined;
  timeZone: string;
}>) {
  const allowed =
    organizationId !== undefined &&
    storeId !== undefined &&
    can(access, "INVENTORY_READ", { organizationId, storeId });

  if (!allowed) {
    return (
      <ErrorState
        title="Accès refusé"
        description="Votre accès ne permet pas de consulter les valorisations de ce magasin."
      />
    );
  }

  return productId === undefined ? (
    <InventoryValuationOverview
      access={access!}
      api={api}
      currency={currency}
      locale={locale}
      organizationId={organizationId!}
      storeId={storeId!}
    />
  ) : (
    <InventoryValuationDetails
      access={access!}
      api={api}
      currency={currency}
      locale={locale}
      organizationId={organizationId!}
      productId={productId}
      storeId={storeId!}
      timeZone={timeZone}
    />
  );
}

function InventoryValuationOverview({
  access,
  api,
  currency,
  locale,
  organizationId,
  storeId,
}: Readonly<{
  access: EffectiveAccess;
  api: FoundationApi | undefined;
  currency: string;
  locale: string;
  organizationId: string;
  storeId: string;
}>) {
  const overview = useQuery({
    enabled: api !== undefined,
    queryKey: [
      ...queryKeys.inventoryValuations.list(organizationId, storeId),
      { authorizationVersion: access.authorizationVersion },
    ],
    queryFn: async () => {
      const [stocks, valuations] = await Promise.all([
        listStocks(api!, storeId, access),
        listInventoryValuations(api!, storeId, currency, access),
      ]);
      const stocksByProduct = new Map(stocks.map((stock) => [stock.productId, stock]));
      const valuationsByProduct = new Map<string, InventoryValuationResource>();
      for (const valuation of valuations) {
        const stock = stocksByProduct.get(valuation.productId);
        if (stock === undefined || stock.id !== valuation.stockId) {
          throw new ApiContractError("The valuation does not match an active Stock position.");
        }
        valuationsByProduct.set(valuation.productId, valuation);
      }
      return stocks.map((stock) => ({
        stock,
        ...(valuationsByProduct.get(stock.productId) === undefined
          ? {}
          : { valuation: valuationsByProduct.get(stock.productId)! }),
      }));
    },
  });

  return (
    <InventoryValuationList
      currency={currency}
      {...(overview.error === null ? {} : { error: overview.error })}
      isLoading={overview.isLoading}
      locale={locale}
      onRetry={() => void overview.refetch()}
      {...(overview.data === undefined ? {} : { rows: overview.data })}
    />
  );
}

function InventoryValuationDetails({
  access,
  api,
  currency,
  locale,
  organizationId,
  productId,
  storeId,
  timeZone,
}: Readonly<{
  access: EffectiveAccess;
  api: FoundationApi | undefined;
  currency: string;
  locale: string;
  organizationId: string;
  productId: string;
  storeId: string;
  timeZone: string;
}>) {
  const [cursorHistory, setCursorHistory] = useState<readonly string[]>([]);
  const cursor = cursorHistory.at(-1);
  const detail = useQuery({
    enabled: api !== undefined,
    queryKey: [
      ...queryKeys.inventoryValuations.detail(organizationId, storeId, productId),
      { authorizationVersion: access.authorizationVersion },
    ],
    queryFn: async () => {
      const [stock, valuation] = await Promise.all([
        getStock(api!, storeId, productId, access),
        getInventoryValuation(api!, storeId, productId, currency, access),
      ]);
      if (stock !== undefined && valuation !== undefined && stock.id !== valuation.stockId) {
        throw new ApiContractError("The valuation does not match the Stock position.");
      }
      return { stock: stock ?? null, valuation: valuation ?? null };
    },
  });
  const ledger = useQuery({
    enabled:
      api !== undefined && detail.data?.valuation !== null && detail.data?.valuation !== undefined,
    queryKey: [
      ...queryKeys.inventoryValuations.movements(organizationId, storeId, productId),
      { authorizationVersion: access.authorizationVersion, cursor: cursor ?? "" },
    ],
    queryFn: () =>
      listInventoryValuationMovements(api!, storeId, productId, currency, access, {
        ...(cursor === undefined ? {} : { cursor }),
        limit: LEDGER_PAGE_SIZE,
      }),
  });

  if (detail.isLoading) return <Spinner label="Chargement de la valorisation" />;
  if (detail.error !== null) {
    return <ValuationLoadError error={detail.error} onRetry={() => void detail.refetch()} />;
  }
  if (detail.data?.stock === null || detail.data?.stock === undefined) {
    return (
      <ErrorState
        title="Position introuvable"
        description="Cette position n’existe pas dans le magasin actif ou n’est pas accessible."
      />
    );
  }

  const { stock, valuation } = detail.data;
  if (valuation === null) {
    return (
      <>
        <a href="/admin/inventory/valuations">Retour aux valorisations</a>
        <h2>Produit {productId}</h2>
        <p>Quantité physique : {formatQuantity(stock.quantityOnHand, { locale })}</p>
        <EmptyState
          title="Valorisation non initialisée"
          description={`Cette position Stock ne possède encore aucune valorisation dans la devise ${currency}.`}
        />
      </>
    );
  }

  const pageNumber = cursorHistory.length + 1;
  return (
    <div className="zandu-page-stack">
      <a href="/admin/inventory/valuations">Retour aux valorisations</a>
      <header>
        <p className="zandu-eyebrow">Valorisation Inventory</p>
        <h2>Produit {productId}</h2>
        <p>Devise du magasin : {currency}</p>
      </header>
      <section aria-labelledby="valuation-summary">
        <h3 id="valuation-summary">État de la valorisation</h3>
        <dl>
          <dt>Quantité valorisée</dt>
          <dd>{formatQuantity(valuation.quantityOnHand, { locale })}</dd>
          <dt>Coût moyen</dt>
          <dd>{formatMoney(valuation.averageUnitCost, { currency, locale })}</dd>
          <dt>Valeur totale</dt>
          <dd>{formatMoney(valuation.totalValue, { currency, locale })}</dd>
          <dt>Version</dt>
          <dd>{valuation.version}</dd>
        </dl>
      </section>
      <section aria-labelledby="valuation-ledger">
        <h3 id="valuation-ledger">Ledger de valorisation</h3>
        <InventoryValuationMovementList
          {...(ledger.error === null ? {} : { error: ledger.error })}
          isLoading={ledger.isLoading}
          locale={locale}
          onPageChange={(nextPage) => {
            if (nextPage < pageNumber) {
              setCursorHistory((history) => history.slice(0, Math.max(0, nextPage - 1)));
            } else if (nextPage === pageNumber + 1 && ledger.data?.nextCursor !== undefined) {
              setCursorHistory((history) => [...history, ledger.data!.nextCursor!]);
            }
          }}
          onRetry={() => void ledger.refetch()}
          {...(ledger.data === undefined ? {} : { page: ledger.data })}
          pageNumber={pageNumber}
          pageSize={LEDGER_PAGE_SIZE}
          timeZone={timeZone}
        />
      </section>
    </div>
  );
}
