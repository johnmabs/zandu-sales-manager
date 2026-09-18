"use client";

import { useMutation, useQuery } from "@tanstack/react-query";
import { ApiRequestError } from "@zandu/api-client";
import { can, useEffectiveAccess } from "@zandu/authorization";
import { formatQuantity } from "@zandu/domain-formatting";
import { ErrorMapper } from "@zandu/error-contract";
import { useNotifications } from "@zandu/notifications/react";
import { useOrganizationContext } from "@zandu/organization-context";
import { queryKeys } from "@zandu/server-state";
import { Button, ErrorState, Spinner } from "@zandu/ui";

import { useAdminRuntime } from "../../../runtime/AdminRuntime";
import { adjustStock, getStock, initializeStock } from "../api/readInventory";
import { hasUnknownInventoryMutationOutcome } from "../mutationSafety";

import { AdjustStockForm } from "./AdjustStockForm";
import { InitializeStockForm } from "./InitializeStockForm";

import type { QueryClient } from "@tanstack/react-query";
import type { FoundationApi, StockAdjustInput, StockInitializeInput } from "@zandu/api-client";
import type { EffectiveAccess } from "@zandu/authorization";

export function StockPositionDetailsPage({ productId }: Readonly<{ productId: string }>) {
  const access = useEffectiveAccess();
  const { activeOrganizationId } = useOrganizationContext();
  const { api, queryClient, storeState } = useAdminRuntime();
  const notifications = useNotifications();
  const store = storeState?.status === "ACTIVE" ? storeState.activeStore : undefined;

  return (
    <StockPositionDetailsWorkspace
      key={`${activeOrganizationId}:${store?.id}:${productId}:${access?.authorizationVersion}`}
      access={access}
      api={api}
      currency={store?.currency ?? "XAF"}
      locale={store?.locale ?? "fr-FR"}
      notifications={notifications}
      organizationId={activeOrganizationId}
      productId={productId}
      queryClient={queryClient}
      storeId={store?.id}
    />
  );
}

export function StockPositionDetailsWorkspace({
  access,
  api,
  currency,
  locale,
  notifications,
  organizationId,
  productId,
  queryClient,
  storeId,
}: Readonly<{
  access: EffectiveAccess | undefined;
  api: FoundationApi | undefined;
  currency: string;
  locale: string;
  notifications: ReturnType<typeof useNotifications>;
  organizationId: string | undefined;
  productId: string;
  queryClient: QueryClient;
  storeId: string | undefined;
}>) {
  const allowed =
    organizationId !== undefined &&
    storeId !== undefined &&
    can(access, "INVENTORY_READ", { organizationId, storeId });
  const metadataAllowed =
    organizationId !== undefined && can(access, "PRODUCT_READ", { organizationId });
  const initializeAllowed =
    organizationId !== undefined &&
    storeId !== undefined &&
    can(access, "INVENTORY_INITIALIZE", { organizationId, storeId });
  const adjustAllowed =
    organizationId !== undefined &&
    storeId !== undefined &&
    can(access, "INVENTORY_ADJUST", { organizationId, storeId });
  const stockQueryKey = [
    ...queryKeys.stock.detail(
      organizationId ?? "unresolved-organization",
      storeId ?? "unresolved-store",
      productId,
    ),
    { authorizationVersion: access?.authorizationVersion ?? 0 },
  ] as const;
  const stock = useQuery({
    enabled: api !== undefined && access !== undefined && allowed,
    queryKey: stockQueryKey,
    queryFn: async () => (await getStock(api!, storeId!, productId, access!)) ?? null,
  });
  const product = useQuery({
    enabled:
      api !== undefined &&
      access !== undefined &&
      allowed &&
      metadataAllowed &&
      stock.isSuccess &&
      (stock.data !== null || initializeAllowed),
    queryKey: [
      ...queryKeys.products.detail(organizationId ?? "unresolved-organization", productId),
      { authorizationVersion: access?.authorizationVersion ?? 0 },
    ],
    queryFn: async () => (await api!.getProduct(productId, access!)) ?? null,
  });
  const initialize = useMutation({
    mutationFn: (input: StockInitializeInput) => {
      if (
        api === undefined ||
        access === undefined ||
        storeId === undefined ||
        !initializeAllowed
      ) {
        throw new Error("Le contexte d’initialisation Stock est indisponible.");
      }
      return initializeStock(api, storeId, productId, input, access);
    },
    onError: async (error) => {
      if (!hasUnknownInventoryMutationOutcome(error)) return;
      await invalidateStockProjections(queryClient, organizationId, storeId, productId, true);
    },
    onSuccess: async (created) => {
      queryClient.setQueryData(stockQueryKey, created);
      await invalidateStockProjections(queryClient, organizationId, storeId, productId, false);
      notifications.notify({ message: "Stock initialisé.", tone: "success" });
    },
  });
  const adjust = useMutation({
    mutationFn: (input: StockAdjustInput) => {
      if (api === undefined || access === undefined || storeId === undefined || !adjustAllowed) {
        throw new Error("Le contexte d’ajustement Stock est indisponible.");
      }
      return adjustStock(api, storeId, productId, input, access);
    },
    onError: async (error) => {
      if (!hasUnknownInventoryMutationOutcome(error)) return;
      await invalidateStockProjections(queryClient, organizationId, storeId, productId, true);
    },
    onSuccess: async (updated) => {
      queryClient.setQueryData(stockQueryKey, updated);
      await invalidateStockProjections(queryClient, organizationId, storeId, productId, false);
      notifications.notify({ message: "Stock ajusté.", tone: "success" });
    },
  });

  if (!allowed) {
    return (
      <ErrorState
        title="Accès refusé"
        description="Votre accès ne permet pas de consulter cette position de stock."
      />
    );
  }
  if (stock.isLoading || (metadataAllowed && stock.isSuccess && product.isLoading)) {
    return <Spinner label="Chargement de la position de stock" />;
  }
  const error = stock.error ?? (metadataAllowed ? product.error : undefined);
  if (error !== null && error !== undefined) {
    return (
      <StockPositionLoadError
        error={error}
        onRetry={() => {
          void stock.refetch();
          if (metadataAllowed && stock.isSuccess) {
            void product.refetch();
          }
        }}
      />
    );
  }
  if (stock.data === undefined || stock.data === null) {
    if (initializeAllowed) {
      const productLabel = product.data?.name ?? `Produit ${productId}`;
      return (
        <div className="zandu-page-stack">
          <header>
            <p className="zandu-eyebrow">Nouvelle position de stock</p>
            <h2>{productLabel}</h2>
            <p>{product.data?.productCode ?? productId}</p>
          </header>
          <InitializeStockForm
            currency={currency}
            productLabel={productLabel}
            onInitialize={async (input) => {
              await initialize.mutateAsync(input);
            }}
          />
        </div>
      );
    }
    return (
      <ErrorState
        title="Position introuvable"
        description="Cette position n’existe pas dans le magasin actif ou n’est pas accessible."
      />
    );
  }

  const item = stock.data;
  const productLabel = product.data?.name ?? `Produit ${item.productId}`;
  const encodedProductId = encodeURIComponent(item.productId);

  return (
    <div className="zandu-page-stack">
      <header>
        <p className="zandu-eyebrow">Position de stock</p>
        <h2>{productLabel}</h2>
        <p>{product.data?.productCode ?? item.productId}</p>
      </header>
      <section aria-labelledby="stock-position-general">
        <h3 id="stock-position-general">État de la position</h3>
        <dl>
          <dt>Quantité disponible</dt>
          <dd>{formatQuantity(item.quantityOnHand, { locale })}</dd>
          <dt>Initialisation</dt>
          <dd>{item.initialized ? "Initialisée" : "À initialiser"}</dd>
          <dt>Version</dt>
          <dd>{item.version}</dd>
          <dt>Identifiant Stock</dt>
          <dd>{item.id}</dd>
          <dt>Identifiant Produit</dt>
          <dd>{item.productId}</dd>
        </dl>
      </section>
      {adjustAllowed && item.initialized ? (
        <section aria-labelledby="stock-position-adjustment">
          <h3 id="stock-position-adjustment">Ajuster la position</h3>
          <AdjustStockForm
            currency={currency}
            onAdjust={async (input) => {
              await adjust.mutateAsync(input);
            }}
          />
        </section>
      ) : null}
      <nav aria-label="Ressources liées à la position">
        {can(access, "STOCK_MOVEMENT_READ", { organizationId: organizationId!, storeId }) ? (
          <a href={`/admin/inventory/movements?productId=${encodedProductId}`}>
            Voir les mouvements
          </a>
        ) : null}
        <a href={`/admin/inventory/valuations?productId=${encodedProductId}`}>
          Voir la valorisation
        </a>
      </nav>
    </div>
  );
}

async function invalidateStockProjections(
  queryClient: QueryClient,
  organizationId: string | undefined,
  storeId: string | undefined,
  productId: string,
  includeDetail: boolean,
) {
  if (organizationId === undefined || storeId === undefined) return;
  await Promise.all([
    queryClient.invalidateQueries({ queryKey: queryKeys.stock.list(organizationId, storeId) }),
    queryClient.invalidateQueries({
      queryKey: queryKeys.stockMovements.list(organizationId, storeId),
    }),
    queryClient.invalidateQueries({
      queryKey: queryKeys.inventoryValuations.list(organizationId, storeId),
    }),
    queryClient.invalidateQueries({
      queryKey: queryKeys.inventoryValuations.detail(organizationId, storeId, productId),
    }),
    queryClient.invalidateQueries({
      queryKey: queryKeys.inventoryValuations.movements(organizationId, storeId, productId),
    }),
    ...(includeDetail
      ? [
          queryClient.invalidateQueries({
            queryKey: queryKeys.stock.detail(organizationId, storeId, productId),
          }),
        ]
      : []),
  ]);
}

function StockPositionLoadError({
  error,
  onRetry,
}: Readonly<{ error: unknown; onRetry: () => void }>) {
  const mapped = new ErrorMapper().map(
    error instanceof ApiRequestError ? error.apiError : { kind: "response", status: 500 },
  );

  return (
    <ErrorState
      title={mapped.title}
      description={mapped.message}
      action={
        <>
          {mapped.retryable ? (
            <Button onClick={onRetry} type="button">
              Réessayer
            </Button>
          ) : null}
          {mapped.correlationId === undefined ? null : (
            <p>Référence de diagnostic : {mapped.correlationId}</p>
          )}
        </>
      }
    />
  );
}
