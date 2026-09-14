"use client";

import { useMutation, useQuery } from "@tanstack/react-query";
import { can, useEffectiveAccess } from "@zandu/authorization";
import { useNotifications } from "@zandu/notifications/react";
import { useOrganizationContext } from "@zandu/organization-context";
import { queryKeys } from "@zandu/server-state";
import { Button, EmptyState, ErrorState, Input, Spinner } from "@zandu/ui";
import { useState } from "react";

import { useAdminRuntime } from "../../../runtime/AdminRuntime";

import type {
  PackagingCreateInput,
  PackagingUpdateInput,
  ProductPackagingResource,
} from "@zandu/api-client";

export function ProductDetailsPage({ productId }: Readonly<{ productId: string }>) {
  const access = useEffectiveAccess();
  const { activeOrganizationId } = useOrganizationContext();
  const { api, queryClient } = useAdminRuntime();
  const notifications = useNotifications();
  const allowed =
    activeOrganizationId !== undefined &&
    can(access, "PRODUCT_READ", { organizationId: activeOrganizationId });
  const product = useQuery({
    enabled: api !== undefined && access !== undefined && allowed,
    queryKey: queryKeys.products.detail(activeOrganizationId ?? "unresolved", productId),
    queryFn: () => api!.getProduct(productId, access!),
  });
  const packagings = useQuery({
    enabled: api !== undefined && access !== undefined && allowed,
    queryKey: queryKeys.productPackagings.list(activeOrganizationId ?? "unresolved", productId),
    queryFn: () => api!.listProductPackagings(productId, access!),
  });
  const refresh = async () => {
    await queryClient.invalidateQueries({ queryKey: ["products", activeOrganizationId] });
    await queryClient.invalidateQueries({
      queryKey: ["productPackagings", activeOrganizationId, productId],
    });
  };
  const transition = useMutation({
    mutationFn: (action: "activate" | "archive" | "deactivate" | "reactivate") =>
      api!.transitionProduct(productId, action),
    onSuccess: refresh,
  });
  const createPackaging = useMutation({
    mutationFn: (input: PackagingCreateInput) => api!.createProductPackaging(productId, input),
    onSuccess: refresh,
  });
  const updatePackaging = useMutation({
    mutationFn: ({ id, input }: { id: string; input: PackagingUpdateInput }) =>
      api!.updateProductPackaging(productId, id, input),
    onSuccess: refresh,
  });
  const transitionPackaging = useMutation({
    mutationFn: ({ id, action }: { id: string; action: "archive" | "deactivate" }) =>
      api!.transitionProductPackaging(productId, id, action),
    onSuccess: refresh,
  });
  if (!allowed)
    return (
      <ErrorState
        title="Accès refusé"
        description="Votre accès ne permet pas de consulter ce produit."
      />
    );
  if (product.isLoading) return <Spinner label="Chargement du produit" />;
  if (product.error)
    return (
      <ErrorState
        title="Produit indisponible"
        description="Le produit n’a pas pu être chargé."
        action={<Button onClick={() => void product.refetch()}>Réessayer</Button>}
      />
    );
  if (!product.data)
    return (
      <ErrorState
        title="Produit introuvable"
        description="Ce produit n’existe pas dans l’organisation active."
      />
    );
  const item = product.data;
  const mayUpdate = can(access, "PRODUCT_UPDATE", { organizationId: activeOrganizationId! });
  const doTransition = (action: "activate" | "archive" | "deactivate" | "reactivate") => {
    if (
      (action === "archive" || action === "deactivate") &&
      !window.confirm(action === "archive" ? "Archiver ce produit ?" : "Désactiver ce produit ?")
    )
      return;
    void transition
      .mutateAsync(action)
      .then(() =>
        notifications.notify({ message: "Statut du produit mis à jour.", tone: "success" }),
      );
  };
  return (
    <main className="zandu-page-stack">
      <header>
        <p className="zandu-eyebrow">Catalogue</p>
        <h1>{item.name}</h1>
        <p>
          {item.productCode} · {item.status}
        </p>
        {mayUpdate ? <a href={`/admin/catalog/products/${item.id}/edit`}>Modifier</a> : null}
      </header>
      <section aria-labelledby="general">
        <h2 id="general">Général</h2>
        <dl>
          <dt>Type</dt>
          <dd>{item.type}</dd>
          <dt>Unité de base</dt>
          <dd>{item.baseUnitId}</dd>
          <dt>Suivi de stock</dt>
          <dd>{item.inventoryTracked ? "Activé" : "Désactivé"}</dd>
          <dt>Catégorie</dt>
          <dd>{item.categoryId ?? "Sans catégorie"}</dd>
          <dt>Description</dt>
          <dd>{item.description ?? "Aucune"}</dd>
        </dl>
      </section>
      <section aria-labelledby="packaging">
        <h2 id="packaging">Conditionnements</h2>
        {packagings.isLoading ? (
          <Spinner label="Chargement des conditionnements" />
        ) : packagings.data?.length ? (
          <ul>
            {packagings.data.map((p) => (
              <li key={p.id}>
                <strong>{p.name}</strong> ({p.code}) — × {p.conversionFactor} · vente{" "}
                {p.allowedForSale ? "oui" : "non"} · achat {p.allowedForPurchase ? "oui" : "non"} ·{" "}
                {p.status}
                {mayUpdate ? (
                  <PackagingActions
                    packaging={p}
                    onSave={(input) => updatePackaging.mutateAsync({ id: p.id, input })}
                    onTransition={(action) => transitionPackaging.mutate({ id: p.id, action })}
                  />
                ) : null}
              </li>
            ))}
          </ul>
        ) : (
          <EmptyState
            title="Aucun conditionnement"
            description="Ajoutez le premier conditionnement commercial de ce produit."
          />
        )}
        {mayUpdate ? (
          <PackagingCreateForm
            onCreate={async (input) => {
              await createPackaging.mutateAsync(input);
              notifications.notify({ message: "Conditionnement créé.", tone: "success" });
            }}
          />
        ) : null}
      </section>
      <section>
        <h2>Codes-barres</h2>
        <p>Les codes-barres seront gérés dans la prochaine unité de travail.</p>
      </section>
      <section>
        <h2>Résumé tarifaire</h2>
        <p>La tarification reste gérée par la feature Tarification.</p>
      </section>
      <section>
        <h2>Cycle de vie</h2>
        {item.status === "DRAFT" &&
        can(access, "PRODUCT_ACTIVATE", { organizationId: activeOrganizationId! }) ? (
          <Button onClick={() => doTransition("activate")}>Activer</Button>
        ) : null}
        {item.status === "ACTIVE" &&
        can(access, "PRODUCT_DEACTIVATE", { organizationId: activeOrganizationId! }) ? (
          <Button onClick={() => doTransition("deactivate")}>Désactiver</Button>
        ) : null}
        {item.status === "INACTIVE" &&
        can(access, "PRODUCT_ACTIVATE", { organizationId: activeOrganizationId! }) ? (
          <Button onClick={() => doTransition("reactivate")}>Réactiver</Button>
        ) : null}
        {item.status !== "ARCHIVED" &&
        can(access, "PRODUCT_ARCHIVE", { organizationId: activeOrganizationId! }) ? (
          <Button onClick={() => doTransition("archive")}>Archiver</Button>
        ) : null}
      </section>
    </main>
  );
}

function PackagingCreateForm({
  onCreate,
}: {
  onCreate: (input: PackagingCreateInput) => Promise<void>;
}) {
  return (
    <form
      aria-label="Nouveau conditionnement"
      onSubmit={(event) => {
        event.preventDefault();
        const d = new FormData(event.currentTarget);
        void onCreate({
          code: String(d.get("code")),
          name: String(d.get("name")),
          unitId: String(d.get("unitId")),
          conversionFactor: String(d.get("conversionFactor")),
          precision: Number(d.get("precision")),
          minimumQuantity: String(d.get("minimumQuantity")),
          quantityIncrement: String(d.get("quantityIncrement")),
          allowedForSale: d.get("allowedForSale") === "on",
          allowedForPurchase: d.get("allowedForPurchase") === "on",
        });
      }}
    >
      <h3>Ajouter un conditionnement</h3>
      {[
        "code",
        "name",
        "unitId",
        "conversionFactor",
        "precision",
        "minimumQuantity",
        "quantityIncrement",
      ].map((name) => (
        <label key={name}>
          {name}
          <Input name={name} required />
        </label>
      ))}
      <label>
        <input type="checkbox" name="allowedForSale" /> Vente
      </label>
      <label>
        <input type="checkbox" name="allowedForPurchase" /> Achat
      </label>
      <Button type="submit">Ajouter</Button>
    </form>
  );
}
function PackagingActions({
  packaging,
  onSave,
  onTransition,
}: {
  packaging: ProductPackagingResource;
  onSave: (input: PackagingUpdateInput) => Promise<unknown>;
  onTransition: (action: "archive" | "deactivate") => void;
}) {
  const [editing, setEditing] = useState(false);
  return editing ? (
    <form
      onSubmit={(e) => {
        e.preventDefault();
        const d = new FormData(e.currentTarget);
        void onSave({
          name: String(d.get("name")),
          minimumQuantity: String(d.get("minimumQuantity")),
          quantityIncrement: String(d.get("quantityIncrement")),
          allowedForSale: d.get("allowedForSale") === "on",
          allowedForPurchase: d.get("allowedForPurchase") === "on",
        }).then(() => setEditing(false));
      }}
    >
      <Input name="name" defaultValue={packaging.name} />
      <Input name="minimumQuantity" defaultValue={packaging.minimumQuantity} />
      <Input name="quantityIncrement" defaultValue={packaging.quantityIncrement} />
      <label>
        <input name="allowedForSale" type="checkbox" defaultChecked={packaging.allowedForSale} />{" "}
        Vente
      </label>
      <label>
        <input
          name="allowedForPurchase"
          type="checkbox"
          defaultChecked={packaging.allowedForPurchase}
        />{" "}
        Achat
      </label>
      <p>
        Le facteur de conversion de ce conditionnement ne peut plus être modifié. Créez un nouveau
        conditionnement si la conversion change.
      </p>
      <Button type="submit">Enregistrer</Button>
    </form>
  ) : (
    <span>
      <Button onClick={() => setEditing(true)}>Modifier</Button>
      {packaging.status === "ACTIVE" ? (
        <Button
          onClick={() => {
            if (window.confirm("Désactiver ce conditionnement ?")) onTransition("deactivate");
          }}
        >
          Désactiver
        </Button>
      ) : null}
      {packaging.status !== "ARCHIVED" ? (
        <Button
          onClick={() => {
            if (window.confirm("Archiver ce conditionnement ?")) onTransition("archive");
          }}
        >
          Archiver
        </Button>
      ) : null}
    </span>
  );
}
