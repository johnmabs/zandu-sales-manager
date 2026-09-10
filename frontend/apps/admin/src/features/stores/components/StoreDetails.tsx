"use client";

import { ApiRequestError } from "@zandu/api-client";
import { Badge, Button, ErrorState, Spinner } from "@zandu/ui";
import Link from "next/link";

import type { StoreResource } from "@zandu/api-client";

export type StoreActionAvailability = Readonly<{
  edit: boolean;
  reactivate: boolean;
  requestClosure: boolean;
  suspend: boolean;
}>;

type StoreDetailsProperties = Readonly<{
  actions: StoreActionAvailability;
  error?: unknown | undefined;
  isLoading: boolean;
  onSuspend?: () => void;
  store?: StoreResource | undefined;
}>;

export function StoreDetails({
  actions,
  error,
  isLoading,
  onSuspend,
  store,
}: StoreDetailsProperties) {
  if (isLoading) {
    return <Spinner label="Chargement du magasin" />;
  }

  if (error !== undefined) {
    return <ErrorState {...storeDetailsErrorPresentation(error)} />;
  }

  if (store === undefined) {
    return (
      <ErrorState
        description="Ce magasin est introuvable ou n’est pas accessible dans l’organisation active."
        title="Magasin introuvable"
      />
    );
  }

  return (
    <>
      <header>
        <h1>{store.name}</h1>
        <p>Code : {store.code}</p>
        <StoreStatus status={store.status} />
      </header>
      <section aria-labelledby="store-profile-title">
        <h2 id="store-profile-title">Profil</h2>
        <dl>
          <dt>Adresse</dt>
          <dd>{store.address ?? "Non renseignée"}</dd>
          <dt>Devise</dt>
          <dd>{store.currency}</dd>
          <dt>Langue</dt>
          <dd>{store.locale}</dd>
          <dt>Fuseau horaire</dt>
          <dd>{store.timeZone}</dd>
        </dl>
      </section>
      <section aria-labelledby="store-closure-title">
        <h2 id="store-closure-title">Fermeture</h2>
        <p>
          {store.status === "CLOSURE_PENDING"
            ? "Une demande de fermeture est en cours."
            : "Aucune demande de fermeture en cours."}
        </p>
      </section>
      <AvailableActions
        actions={actions}
        {...(onSuspend === undefined ? {} : { onSuspend })}
        storeId={store.id}
      />
    </>
  );
}

export function storeDetailsErrorPresentation(error: unknown): Readonly<{
  description: string;
  title: string;
}> {
  if (error instanceof ApiRequestError) {
    if (error.apiError.kind === "response" && error.apiError.status === 404) {
      return {
        description:
          "Ce magasin est introuvable ou n’est pas accessible dans l’organisation active.",
        title: "Magasin introuvable",
      };
    }
    if (error.apiError.kind === "response" && error.apiError.status === 403) {
      return {
        description: "Votre accès ne permet pas de consulter ce magasin.",
        title: "Accès refusé",
      };
    }
  }

  return {
    description: "Le serveur n’a pas pu fournir ce magasin. Réessayez ultérieurement.",
    title: "Impossible de charger le magasin",
  };
}

function AvailableActions({
  actions,
  onSuspend,
  storeId,
}: Readonly<{ actions: StoreActionAvailability; onSuspend?: () => void; storeId: string }>) {
  const labels = [
    ...(actions.edit ? ["Modifier"] : []),
    ...(actions.suspend ? ["Suspendre"] : []),
    ...(actions.reactivate ? ["Réactiver"] : []),
    ...(actions.requestClosure ? ["Demander la fermeture"] : []),
  ];

  if (labels.length === 0) {
    return null;
  }

  return (
    <section aria-labelledby="store-actions-title">
      <h2 id="store-actions-title">Actions disponibles</h2>
      <ul>
        {labels.map((label) => (
          <li key={label}>
            {label === "Modifier" ? (
              <Link href={`/app/stores/${encodeURIComponent(storeId)}/edit`}>{label}</Link>
            ) : label === "Suspendre" && onSuspend !== undefined ? (
              <Button onClick={onSuspend} type="button">
                {label}
              </Button>
            ) : (
              label
            )}
          </li>
        ))}
      </ul>
    </section>
  );
}

function StoreStatus({ status }: Pick<StoreResource, "status">) {
  const tone =
    status === "ACTIVE"
      ? "success"
      : status === "SUSPENDED" || status === "CLOSURE_PENDING"
        ? "warning"
        : "neutral";

  return <Badge tone={tone}>{status}</Badge>;
}
