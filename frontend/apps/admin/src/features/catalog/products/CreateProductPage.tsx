"use client";
import { can, useEffectiveAccess } from "@zandu/authorization";
import { useNotifications } from "@zandu/notifications/react";
import { useOrganizationContext } from "@zandu/organization-context";
import { ErrorState, Spinner } from "@zandu/ui";
import { useRouter } from "next/navigation";

import { useAdminRuntime } from "../../../runtime/AdminRuntime";
import { useCreateProduct } from "../hooks/useCreateProduct";
import { useCatalogSingleFlight } from "../mutationSafety";

import { ProductCreateForm } from "./ProductCreateForm";

import type { ProductCreateInput } from "@zandu/api-client";

export function CreateProductPage() {
  const access = useEffectiveAccess();
  const { activeOrganizationId } = useOrganizationContext();
  const { api, queryClient } = useAdminRuntime();
  const router = useRouter();
  const notifications = useNotifications();
  const singleFlight = useCatalogSingleFlight();
  const create = useCreateProduct({
    api,
    authorizationVersion: access?.authorizationVersion ?? 0,
    organizationId: activeOrganizationId ?? "unresolved-organization",
    queryClient,
  });
  if (access === undefined || activeOrganizationId === undefined)
    return <Spinner label="Chargement des autorisations" />;
  if (!can(access, "PRODUCT_CREATE", { organizationId: activeOrganizationId }))
    return (
      <ErrorState
        title="Action non autorisée"
        description="Votre accès ne permet pas de créer un produit dans l’organisation active."
      />
    );
  const onCreate = async (input: ProductCreateInput) => {
    const product = await singleFlight(() => create.mutateAsync(input));
    if (!product) return;
    notifications.notify({ message: "Produit créé.", tone: "success" });
    router.push(`/admin/catalog/products/${encodeURIComponent(product.id)}`);
  };
  return (
    <section aria-labelledby="create-product-title">
      <h1 id="create-product-title">Créer un produit</h1>
      <ProductCreateForm onCreate={onCreate} />
    </section>
  );
}
