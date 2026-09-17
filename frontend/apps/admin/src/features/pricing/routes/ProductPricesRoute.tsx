"use client";

import { useMutation, useQuery } from "@tanstack/react-query";
import { ApiRequestError } from "@zandu/api-client";
import { can, useEffectiveAccess } from "@zandu/authorization";
import { useNotifications } from "@zandu/notifications/react";
import { useOrganizationContext } from "@zandu/organization-context";
import { queryKeys } from "@zandu/server-state";
import { Button, EmptyState, ErrorState, Input, Spinner } from "@zandu/ui";
import { useState } from "react";

import { useAdminRuntime } from "../../../runtime/AdminRuntime";
import { PricingSection } from "../components/PricingSection";

import type {
  PriceListResource,
  ProductPriceCreateInput,
  ProductPriceResource,
  ProductPriceUpdateInput,
  ProductResource,
} from "@zandu/api-client";

const decimalPattern = /^\d+(?:\.\d+)?$/;

export function ProductPricesRoute() {
  return (
    <PricingSection title="Prix produits" permission="PRODUCT_PRICE_READ">
      <ProductPriceAdministration />
    </PricingSection>
  );
}

function ProductPriceAdministration() {
  const access = useEffectiveAccess();
  const { activeOrganizationId } = useOrganizationContext();
  const { api, queryClient } = useAdminRuntime();
  const notifications = useNotifications();
  const organizationId = activeOrganizationId ?? "unresolved";
  const enabled = api !== undefined && access !== undefined && activeOrganizationId !== undefined;
  const prices = useQuery({
    enabled,
    queryKey: queryKeys.productPrices.list(organizationId),
    queryFn: () => api!.listProductPrices(access!),
  });
  const lists = useQuery({
    enabled,
    queryKey: queryKeys.priceLists.list(organizationId),
    queryFn: () => api!.listPriceLists(access!),
  });
  const products = useQuery({
    enabled,
    queryKey: queryKeys.products.list(organizationId),
    queryFn: () => api!.listProducts(access!),
  });
  const refresh = () =>
    queryClient.invalidateQueries({ queryKey: ["productPrices", organizationId] });
  const create = useMutation({
    mutationFn: (input: ProductPriceCreateInput) => api!.createProductPrice(input),
    onSuccess: refresh,
  });
  const update = useMutation({
    mutationFn: ({ id, input }: { id: string; input: ProductPriceUpdateInput }) =>
      api!.updateProductPrice(id, input),
    onSuccess: refresh,
  });
  const transition = useMutation({
    mutationFn: ({ id, action }: { id: string; action: "activate" | "archive" | "deactivate" }) =>
      api!.transitionProductPrice(id, action),
    onSuccess: refresh,
  });
  if (prices.isLoading || lists.isLoading || products.isLoading)
    return <Spinner label="Chargement des prix produits" />;
  if (prices.error || lists.error || products.error)
    return (
      <ErrorState
        title="Tarification indisponible"
        description="Les données tarifaires n’ont pas pu être chargées."
        action={
          <Button
            onClick={() => {
              void prices.refetch();
              void lists.refetch();
              void products.refetch();
            }}
          >
            Réessayer
          </Button>
        }
      />
    );
  const mayCreate = can(access, "PRODUCT_PRICE_CREATE", { organizationId });
  const mayUpdate = can(access, "PRODUCT_PRICE_UPDATE", { organizationId });
  const mayArchive = can(access, "PRODUCT_PRICE_ARCHIVE", { organizationId });
  const productNames = new Map(products.data?.map((product) => [product.id, product.name]));
  const listNames = new Map(lists.data?.map((list) => [list.id, list.name]));
  return (
    <div className="zandu-page-stack">
      {prices.data?.length ? (
        <ul aria-label="Prix produits">
          {prices.data.map((item) => (
            <ProductPriceItem
              key={item.id}
              item={item}
              label={`${productNames.get(item.productId) ?? item.productId} · ${listNames.get(item.priceListId) ?? item.priceListId}`}
              mayArchive={mayArchive}
              mayUpdate={mayUpdate}
              onSave={(input) => update.mutateAsync({ id: item.id, input })}
              onTransition={async (action) => {
                if (
                  (action === "archive" || action === "deactivate") &&
                  !window.confirm(
                    action === "archive" ? "Archiver ce prix ?" : "Désactiver ce prix ?",
                  )
                )
                  return;
                await transition.mutateAsync({ id: item.id, action });
                notifications.notify({ message: "Statut du prix mis à jour.", tone: "success" });
              }}
            />
          ))}
        </ul>
      ) : (
        <EmptyState
          title="Aucun prix produit"
          description="Créez un prix pour un produit et un conditionnement."
        />
      )}
      {mayCreate ? (
        <ProductPriceCreateForm
          lists={lists.data ?? []}
          products={products.data ?? []}
          onCreate={async (input) => {
            await create.mutateAsync(input);
            notifications.notify({ message: "Prix produit créé.", tone: "success" });
          }}
        />
      ) : null}
      <EffectivePriceResolver products={products.data ?? []} />
    </div>
  );
}

function ProductPriceItem({
  item,
  label,
  mayArchive,
  mayUpdate,
  onSave,
  onTransition,
}: Readonly<{
  item: ProductPriceResource;
  label: string;
  mayArchive: boolean;
  mayUpdate: boolean;
  onSave: (input: ProductPriceUpdateInput) => Promise<unknown>;
  onTransition: (action: "activate" | "archive" | "deactivate") => Promise<void>;
}>) {
  const [editing, setEditing] = useState(false);
  return (
    <li>
      <h2>{label}</h2>
      <p>
        {item.amount} {item.currency} · {item.status}
      </p>
      <p>
        Conditionnement : {item.packagingId} · validité : {item.validFrom ?? "immédiate"} →{" "}
        {item.validTo ?? "sans fin"}
      </p>
      {editing ? (
        <ProductPriceFields
          initial={item}
          submitLabel="Enregistrer"
          onSubmit={async (values) => {
            await onSave({ ...values, expectedVersion: item.version });
            setEditing(false);
          }}
        />
      ) : null}
      {mayUpdate && !editing ? <Button onClick={() => setEditing(true)}>Modifier</Button> : null}
      {mayUpdate && item.status === "INACTIVE" ? (
        <Button onClick={() => void onTransition("activate")}>Activer</Button>
      ) : null}
      {mayUpdate && item.status === "ACTIVE" ? (
        <Button onClick={() => void onTransition("deactivate")}>Désactiver</Button>
      ) : null}
      {mayArchive && item.status !== "ARCHIVED" ? (
        <Button onClick={() => void onTransition("archive")}>Archiver</Button>
      ) : null}
    </li>
  );
}

function ProductPriceCreateForm({
  lists,
  products,
  onCreate,
}: Readonly<{
  lists: readonly PriceListResource[];
  products: readonly ProductResource[];
  onCreate: (input: ProductPriceCreateInput) => Promise<void>;
}>) {
  const access = useEffectiveAccess();
  const { activeOrganizationId } = useOrganizationContext();
  const { api } = useAdminRuntime();
  const [productId, setProductId] = useState(products[0]?.id ?? "");
  const packagings = useQuery({
    enabled: api !== undefined && access !== undefined && productId !== "",
    queryKey: queryKeys.productPackagings.list(
      activeOrganizationId ?? "unresolved",
      productId || "unselected",
    ),
    queryFn: () => api!.listProductPackagings(productId, access!),
  });
  return (
    <form
      onSubmit={(event) => {
        event.preventDefault();
        const data = new FormData(event.currentTarget);
        const amount = String(data.get("amount")).trim();
        if (!decimalPattern.test(amount)) return;
        const list = lists.find((candidate) => candidate.id === String(data.get("priceListId")));
        const validFrom = String(data.get("validFrom") ?? "").trim();
        const validTo = String(data.get("validTo") ?? "").trim();
        void onCreate({
          priceListId: String(data.get("priceListId")),
          productId,
          packagingId: String(data.get("packagingId")),
          amount,
          currency: list?.currency ?? "",
          validFrom: validFrom || null,
          validTo: validTo || null,
        });
      }}
    >
      <h2>Créer un prix produit</h2>
      <label>
        Liste de prix{" "}
        <select name="priceListId" required>
          {lists
            .filter((item) => item.status !== "ARCHIVED")
            .map((item) => (
              <option key={item.id} value={item.id}>
                {item.name} ({item.currency})
              </option>
            ))}
        </select>
      </label>
      <label>
        Produit{" "}
        <select
          name="productId"
          value={productId}
          onChange={(event) => setProductId(event.target.value)}
          required
        >
          {products.map((product) => (
            <option key={product.id} value={product.id}>
              {product.name}
            </option>
          ))}
        </select>
      </label>
      <label>
        Conditionnement{" "}
        <select name="packagingId" required disabled={packagings.isLoading}>
          {packagings.data
            ?.filter((item) => item.status === "ACTIVE")
            .map((item) => (
              <option key={item.id} value={item.id}>
                {item.name}
              </option>
            ))}
        </select>
      </label>
      <ProductPriceFields submitLabel="Créer" nested />
    </form>
  );
}

function ProductPriceFields({
  initial,
  nested = false,
  onSubmit,
  submitLabel,
}: Readonly<{
  initial?: ProductPriceResource;
  nested?: boolean;
  onSubmit?: (input: Omit<ProductPriceUpdateInput, "expectedVersion">) => Promise<void>;
  submitLabel: string;
}>) {
  const fields = (
    <>
      <label>
        Montant exact{" "}
        <Input
          name="amount"
          inputMode="decimal"
          pattern="\d+(\.\d+)?"
          defaultValue={initial?.amount}
          required
        />
      </label>
      <label>
        Devise{" "}
        <Input
          name="currency"
          defaultValue={initial?.currency}
          required={initial !== undefined}
          readOnly={initial !== undefined}
        />
      </label>
      <label>
        Valide à partir de{" "}
        <Input
          name="validFrom"
          type="datetime-local"
          defaultValue={initial?.validFrom?.slice(0, 16)}
        />
      </label>
      <label>
        Valide jusqu’au{" "}
        <Input name="validTo" type="datetime-local" defaultValue={initial?.validTo?.slice(0, 16)} />
      </label>
      <Button type="submit">{submitLabel}</Button>
    </>
  );
  if (nested) return fields;
  return (
    <form
      onSubmit={(event) => {
        event.preventDefault();
        const data = new FormData(event.currentTarget);
        const amount = String(data.get("amount")).trim();
        if (!decimalPattern.test(amount)) return;
        const validFrom = String(data.get("validFrom") ?? "").trim();
        const validTo = String(data.get("validTo") ?? "").trim();
        void onSubmit?.({
          amount,
          currency: String(data.get("currency")).trim().toUpperCase(),
          validFrom: validFrom || null,
          validTo: validTo || null,
        });
      }}
    >
      {fields}
    </form>
  );
}

function EffectivePriceResolver({ products }: Readonly<{ products: readonly ProductResource[] }>) {
  const access = useEffectiveAccess();
  const { activeOrganizationId } = useOrganizationContext();
  const { api } = useAdminRuntime();
  const [productId, setProductId] = useState(products[0]?.id ?? "");
  const [packagingId, setPackagingId] = useState("");
  const [at, setAt] = useState("");
  const [requested, setRequested] = useState(false);
  const packagings = useQuery({
    enabled: api !== undefined && access !== undefined && productId !== "",
    queryKey: queryKeys.productPackagings.list(
      activeOrganizationId ?? "unresolved",
      productId || "unselected",
    ),
    queryFn: () => api!.listProductPackagings(productId, access!),
  });
  const effective = useQuery({
    enabled: requested && api !== undefined && productId !== "" && packagingId !== "",
    queryKey: queryKeys.productPrices.effective(
      activeOrganizationId ?? "unresolved",
      productId,
      packagingId,
      at || undefined,
    ),
    queryFn: () => api!.getEffectiveProductPrice(productId, packagingId, at || undefined),
    retry: false,
  });
  const noPrice =
    effective.error instanceof ApiRequestError &&
    effective.error.apiError.kind === "response" &&
    effective.error.apiError.status === 404;
  return (
    <section aria-labelledby="effective-price">
      <h2 id="effective-price">Prix effectif</h2>
      <label>
        Produit{" "}
        <select
          value={productId}
          onChange={(event) => {
            setProductId(event.target.value);
            setPackagingId("");
            setRequested(false);
          }}
        >
          {products.map((product) => (
            <option key={product.id} value={product.id}>
              {product.name}
            </option>
          ))}
        </select>
      </label>
      <label>
        Conditionnement{" "}
        <select
          value={packagingId}
          onChange={(event) => {
            setPackagingId(event.target.value);
            setRequested(false);
          }}
        >
          <option value="">Sélectionner</option>
          {packagings.data?.map((item) => (
            <option key={item.id} value={item.id}>
              {item.name}
            </option>
          ))}
        </select>
      </label>
      <label>
        Date métier (facultative){" "}
        <Input
          type="datetime-local"
          value={at}
          onChange={(event) => {
            setAt(event.target.value);
            setRequested(false);
          }}
        />
      </label>
      <Button onClick={() => setRequested(true)} disabled={!productId || !packagingId}>
        Résoudre côté serveur
      </Button>
      {effective.isFetching ? <Spinner label="Résolution du prix" /> : null}
      {effective.data ? (
        <p>
          <strong>
            {effective.data.amount} {effective.data.currency}
          </strong>{" "}
          · liste {effective.data.priceListId}
        </p>
      ) : null}
      {noPrice ? (
        <EmptyState
          title="Aucun prix applicable"
          description="Aucun prix n’est applicable à cette date. Cette absence est distincte d’un prix à zéro."
        />
      ) : null}
      {effective.error && !noPrice ? (
        <ErrorState
          title="Prix indisponible"
          description="Le prix effectif n’a pas pu être résolu."
        />
      ) : null}
    </section>
  );
}
