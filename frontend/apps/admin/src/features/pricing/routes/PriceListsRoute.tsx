"use client";

import { useMutation, useQuery } from "@tanstack/react-query";
import { can, useEffectiveAccess } from "@zandu/authorization";
import { useNotifications } from "@zandu/notifications/react";
import { useOrganizationContext } from "@zandu/organization-context";
import { queryKeys } from "@zandu/server-state";
import { Button, EmptyState, ErrorState, Input, Spinner } from "@zandu/ui";
import { useState } from "react";

import { useAdminRuntime } from "../../../runtime/AdminRuntime";
import { PricingSection } from "../components/PricingSection";

import type {
  PriceListCreateInput,
  PriceListResource,
  PriceListUpdateInput,
} from "@zandu/api-client";

type PriceListFormValues = Readonly<{
  code: string;
  currency?: string;
  name: string;
  priority: number;
  validFrom: string | null;
  validTo: string | null;
}>;

export function PriceListsRoute() {
  return (
    <PricingSection title="Listes de prix" permission="PRICE_LIST_READ">
      <PriceListAdministration />
    </PricingSection>
  );
}

function PriceListAdministration() {
  const access = useEffectiveAccess();
  const { activeOrganizationId } = useOrganizationContext();
  const { api, queryClient } = useAdminRuntime();
  const notifications = useNotifications();
  const organizationId = activeOrganizationId ?? "unresolved";
  const lists = useQuery({
    enabled: api !== undefined && access !== undefined && activeOrganizationId !== undefined,
    queryKey: queryKeys.priceLists.list(organizationId),
    queryFn: () => api!.listPriceLists(access!),
  });
  const refresh = async () => {
    await queryClient.invalidateQueries({ queryKey: ["priceLists", organizationId] });
    await queryClient.invalidateQueries({ queryKey: ["productPrices", organizationId] });
  };
  const create = useMutation({
    mutationFn: (input: PriceListCreateInput) => api!.createPriceList(input),
    onSuccess: refresh,
  });
  const update = useMutation({
    mutationFn: ({ id, input }: { id: string; input: PriceListUpdateInput }) =>
      api!.updatePriceList(id, input),
    onSuccess: refresh,
  });
  const transition = useMutation({
    mutationFn: ({ id, action }: { id: string; action: "activate" | "archive" | "deactivate" }) =>
      api!.transitionPriceList(id, action),
    onSuccess: refresh,
  });

  if (lists.isLoading) return <Spinner label="Chargement des listes de prix" />;
  if (lists.error)
    return (
      <ErrorState
        title="Listes de prix indisponibles"
        description="Les listes de prix n’ont pas pu être chargées."
        action={<Button onClick={() => void lists.refetch()}>Réessayer</Button>}
      />
    );

  const mayCreate = can(access, "PRICE_LIST_CREATE", { organizationId });
  const mayUpdate = can(access, "PRICE_LIST_UPDATE", { organizationId });
  const mayActivate = can(access, "PRICE_LIST_ACTIVATE", { organizationId });
  const mayArchive = can(access, "PRICE_LIST_ARCHIVE", { organizationId });
  return (
    <div className="zandu-page-stack">
      {lists.data?.length ? (
        <ul aria-label="Listes de prix">
          {lists.data.map((item) => (
            <PriceListItem
              key={item.id}
              item={item}
              mayActivate={mayActivate}
              mayArchive={mayArchive}
              mayUpdate={mayUpdate}
              onSave={(input) => update.mutateAsync({ id: item.id, input })}
              onTransition={async (action) => {
                if (
                  (action === "archive" || action === "deactivate") &&
                  !window.confirm(
                    action === "archive" ? "Archiver cette liste ?" : "Désactiver cette liste ?",
                  )
                )
                  return;
                await transition.mutateAsync({ id: item.id, action });
                notifications.notify({
                  message: "Statut de la liste mis à jour.",
                  tone: "success",
                });
              }}
            />
          ))}
        </ul>
      ) : (
        <EmptyState
          title="Aucune liste de prix"
          description="Créez la première liste tarifaire de l’organisation."
        />
      )}
      {mayCreate ? (
        <PriceListForm
          title="Créer une liste de prix"
          submitLabel="Créer"
          onSubmit={async (input) => {
            await create.mutateAsync({ ...input, currency: input.currency ?? "" });
            notifications.notify({ message: "Liste de prix créée.", tone: "success" });
          }}
        />
      ) : null}
    </div>
  );
}

function PriceListItem({
  item,
  mayActivate,
  mayArchive,
  mayUpdate,
  onSave,
  onTransition,
}: Readonly<{
  item: PriceListResource;
  mayActivate: boolean;
  mayArchive: boolean;
  mayUpdate: boolean;
  onSave: (input: PriceListUpdateInput) => Promise<unknown>;
  onTransition: (action: "activate" | "archive" | "deactivate") => Promise<void>;
}>) {
  const [editing, setEditing] = useState(false);
  return (
    <li>
      <h2>{item.name}</h2>
      <p>
        {item.code} · {item.currency} · priorité {item.priority} · {item.status}
      </p>
      <p>
        Validité : {item.validFrom ?? "immédiate"} → {item.validTo ?? "sans fin"}
      </p>
      {editing ? (
        <PriceListForm
          initial={item}
          title="Modifier la liste"
          submitLabel="Enregistrer"
          onSubmit={async (input) => {
            await onSave({
              code: input.code,
              expectedVersion: item.version,
              name: input.name,
              priority: input.priority,
              validFrom: input.validFrom,
              validTo: input.validTo,
            });
            setEditing(false);
          }}
        />
      ) : null}
      {mayUpdate && !editing ? <Button onClick={() => setEditing(true)}>Modifier</Button> : null}
      {mayActivate && (item.status === "DRAFT" || item.status === "INACTIVE") ? (
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

function PriceListForm({
  initial,
  onSubmit,
  submitLabel,
  title,
}: Readonly<{
  initial?: PriceListResource;
  onSubmit: (input: PriceListFormValues) => Promise<void>;
  submitLabel: string;
  title: string;
}>) {
  return (
    <form
      onSubmit={(event) => {
        event.preventDefault();
        const data = new FormData(event.currentTarget);
        const validFrom = String(data.get("validFrom") ?? "").trim();
        const validTo = String(data.get("validTo") ?? "").trim();
        void onSubmit({
          code: String(data.get("code")).trim(),
          name: String(data.get("name")).trim(),
          ...(initial === undefined
            ? { currency: String(data.get("currency")).trim().toUpperCase() }
            : {}),
          priority: Number(data.get("priority")),
          validFrom: validFrom || null,
          validTo: validTo || null,
        });
      }}
    >
      <h2>{title}</h2>
      <label>
        Code <Input name="code" defaultValue={initial?.code} required />
      </label>
      <label>
        Nom <Input name="name" defaultValue={initial?.name} required />
      </label>
      {initial === undefined ? (
        <label>
          Devise <Input name="currency" defaultValue="XAF" required />
        </label>
      ) : null}
      <label>
        Priorité{" "}
        <Input name="priority" type="number" defaultValue={initial?.priority ?? 0} required />
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
    </form>
  );
}
