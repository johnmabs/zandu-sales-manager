"use client";

import { useQuery } from "@tanstack/react-query";
import { ApiRequestError } from "@zandu/api-client";
import { can, useEffectiveAccess } from "@zandu/authorization";
import { formatQuantity } from "@zandu/domain-formatting";
import { ErrorMapper } from "@zandu/error-contract";
import { useOrganizationContext } from "@zandu/organization-context";
import { queryKeys } from "@zandu/server-state";
import { Button, ErrorState, Spinner } from "@zandu/ui";

import { useAdminRuntime } from "../../../runtime/AdminRuntime";
import { getStock } from "../api/readInventory";

import type { FoundationApi } from "@zandu/api-client";
import type { EffectiveAccess } from "@zandu/authorization";

export function StockPositionDetailsPage({ productId }: Readonly<{ productId: string }>) {
  const access = useEffectiveAccess();
  const { activeOrganizationId } = useOrganizationContext();
  const { api, storeState } = useAdminRuntime();
  const store = storeState?.status === "ACTIVE" ? storeState.activeStore : undefined;

  return (
    <StockPositionDetailsWorkspace
      key={`${activeOrganizationId}:${store?.id}:${productId}:${access?.authorizationVersion}`}
      access={access}
      api={api}
      locale={store?.locale ?? "fr-FR"}
      organizationId={activeOrganizationId}
      productId={productId}
      storeId={store?.id}
    />
  );
}

export function StockPositionDetailsWorkspace({
  access,
  api,
  locale,
  organizationId,
  productId,
  storeId,
}: Readonly<{
  access: EffectiveAccess | undefined;
  api: FoundationApi | undefined;
  locale: string;
  organizationId: string | undefined;
  productId: string;
  storeId: string | undefined;
}>) {
  const allowed =
    organizationId !== undefined &&
    storeId !== undefined &&
    can(access, "INVENTORY_READ", { organizationId, storeId });
  const metadataAllowed =
    organizationId !== undefined && can(access, "PRODUCT_READ", { organizationId });
  const stock = useQuery({
    enabled: api !== undefined && access !== undefined && allowed,
    queryKey: [
      ...queryKeys.stock.detail(
        organizationId ?? "unresolved-organization",
        storeId ?? "unresolved-store",
        productId,
      ),
      { authorizationVersion: access?.authorizationVersion ?? 0 },
    ],
    queryFn: async () => (await getStock(api!, storeId!, productId, access!)) ?? null,
  });
  const product = useQuery({
    enabled:
      api !== undefined &&
      access !== undefined &&
      allowed &&
      metadataAllowed &&
      stock.data !== undefined &&
      stock.data !== null,
    queryKey: [
      ...queryKeys.products.detail(organizationId ?? "unresolved-organization", productId),
      { authorizationVersion: access?.authorizationVersion ?? 0 },
    ],
    queryFn: async () => (await api!.getProduct(productId, access!)) ?? null,
  });

  if (!allowed) {
    return (
      <ErrorState
        title="Accès refusé"
        description="Votre accès ne permet pas de consulter cette position de stock."
      />
    );
  }
  if (
    stock.isLoading ||
    (metadataAllowed && stock.data !== undefined && stock.data !== null && product.isLoading)
  ) {
    return <Spinner label="Chargement de la position de stock" />;
  }
  const error = stock.error ?? (metadataAllowed ? product.error : undefined);
  if (error !== null && error !== undefined) {
    return (
      <StockPositionLoadError
        error={error}
        onRetry={() => {
          void stock.refetch();
          if (metadataAllowed && stock.data !== undefined && stock.data !== null) {
            void product.refetch();
          }
        }}
      />
    );
  }
  if (stock.data === undefined || stock.data === null) {
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
