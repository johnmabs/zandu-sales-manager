"use client";

import { useQuery } from "@tanstack/react-query";
import { can, useEffectiveAccess } from "@zandu/authorization";
import { useNotifications } from "@zandu/notifications/react";
import { useOrganizationContext } from "@zandu/organization-context";
import { queryKeys } from "@zandu/server-state";
import { ErrorState, Spinner } from "@zandu/ui";
import { useRouter } from "next/navigation";

import { useAdminRuntime } from "../../../runtime/AdminRuntime";
import { useUpdateProduct } from "../hooks/useUpdateProduct";
import { useCatalogSingleFlight } from "../mutationSafety";

import { ProductUpdateForm } from "./ProductUpdateForm";

import type { ProductUpdateInput } from "@zandu/api-client";

export function EditProductPage({ productId }: Readonly<{ productId: string }>) {
  const access = useEffectiveAccess();
  const { activeOrganizationId } = useOrganizationContext();
  const { api, queryClient } = useAdminRuntime();
  const notifications = useNotifications();
  const router = useRouter();
  const singleFlight = useCatalogSingleFlight();
  const allowed =
    !!activeOrganizationId &&
    can(access, "PRODUCT_UPDATE", { organizationId: activeOrganizationId });
  const details = useQuery({
    enabled: !!api && !!access && allowed,
    queryKey: queryKeys.products.detail(activeOrganizationId ?? "unresolved", productId),
    queryFn: () => api!.getProduct(productId, access!),
  });
  const update = useUpdateProduct({
    api,
    authorizationVersion: access?.authorizationVersion ?? 0,
    organizationId: activeOrganizationId ?? "unresolved",
    productId,
    queryClient,
  });
  if (!access || !activeOrganizationId) return <Spinner label="Chargement des autorisations" />;
  if (!allowed)
    return (
      <ErrorState
        title="Action non autorisée"
        description="Votre accès ne permet pas de modifier ce produit."
      />
    );
  if (details.isLoading) return <Spinner label="Chargement du produit" />;
  if (details.error || !details.data)
    return (
      <ErrorState
        title="Produit introuvable"
        description="Ce produit n’existe pas dans l’organisation active."
      />
    );
  const onUpdate = async (input: ProductUpdateInput) => {
    const updated = await singleFlight(() => update.mutateAsync(input));
    if (!updated) return;
    notifications.notify({ message: "Produit mis à jour.", tone: "success" });
    router.push(`/admin/catalog/products/${encodeURIComponent(productId)}`);
  };
  return (
    <section aria-labelledby="edit-product-title">
      <h1 id="edit-product-title">Modifier le produit</h1>
      <ProductUpdateForm
        key={`${details.data.id}:${details.data.version}`}
        product={details.data}
        onUpdate={onUpdate}
        onConflictReload={() => void details.refetch()}
      />
    </section>
  );
}
