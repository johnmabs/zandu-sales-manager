"use client";

import { Button, ErrorState, Skeleton } from "@zandu/ui";
import Link from "next/link";

import { StoreClosureBlockers } from "./StoreClosureBlockers";
import { StoreErrorState, storeErrorPresentation } from "./StoreErrorState";
import { StoreStatusBadge } from "./StoreStatusBadge";

import type { StoreClosureResource, StoreResource } from "@zandu/api-client";

export type StoreActionAvailability = Readonly<{
  cancelClosure: boolean;
  edit: boolean;
  reactivate: boolean;
  requestClosure: boolean;
  suspend: boolean;
}>;

type StoreDetailsProperties = Readonly<{
  actions: StoreActionAvailability;
  closure?: StoreClosureResource;
  error?: unknown | undefined;
  isLoading: boolean;
  onCancelClosure?: () => void;
  onSuspend?: () => void;
  onRequestClosure?: () => void;
  onRetry?: () => void;
  store?: StoreResource | undefined;
}>;

export function StoreDetails({
  actions,
  closure,
  error,
  isLoading,
  onCancelClosure,
  onSuspend,
  onRequestClosure,
  onRetry,
  store,
}: StoreDetailsProperties) {
  if (isLoading) {
    return <StoreDetailsSkeleton />;
  }

  if (error != null) {
    return (
      <StoreErrorState
        error={error}
        {...(onRetry === undefined ? {} : { onRetry })}
        scope="details"
      />
    );
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
    <div className="zandu-store-details">
      <header className="zandu-store-details__header">
        <h1>{store.name}</h1>
        <p>Code : {store.code}</p>
        <StoreStatusBadge status={store.status} />
      </header>
      <section aria-labelledby="store-profile-title" className="zandu-store-details__section">
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
      <section aria-labelledby="store-closure-title" className="zandu-store-details__section">
        <h2 id="store-closure-title">Fermeture</h2>
        <p>
          {closure === undefined
            ? store.status === "CLOSURE_PENDING"
              ? "Une demande de fermeture est en cours."
              : "Aucune demande de fermeture en cours."
            : `Statut de la demande : ${closure.status}.`}
        </p>
        {closure === undefined ? null : <StoreClosureBlockers blockers={closure.blockers} />}
      </section>
      <AvailableActions
        actions={actions}
        {...(onCancelClosure === undefined ? {} : { onCancelClosure })}
        {...(onSuspend === undefined ? {} : { onSuspend })}
        {...(onRequestClosure === undefined ? {} : { onRequestClosure })}
        storeId={store.id}
      />
    </div>
  );
}

export function StoreDetailsSkeleton() {
  return (
    <div aria-label="Chargement du magasin" aria-busy="true" role="status">
      <Skeleton className="zandu-skeleton--title" />
      <Skeleton className="zandu-skeleton--text" />
      <Skeleton className="zandu-skeleton--section" />
    </div>
  );
}

export function storeDetailsErrorPresentation(error: unknown): Readonly<{
  description: string;
  title: string;
}> {
  const presentation = storeErrorPresentation(error, "details");
  return { description: presentation.description, title: presentation.title };
}

function AvailableActions({
  actions,
  onCancelClosure,
  onSuspend,
  onRequestClosure,
  storeId,
}: Readonly<{
  actions: StoreActionAvailability;
  onCancelClosure?: () => void;
  onRequestClosure?: () => void;
  onSuspend?: () => void;
  storeId: string;
}>) {
  const labels = [
    ...(actions.cancelClosure ? ["Annuler la fermeture"] : []),
    ...(actions.edit ? ["Modifier"] : []),
    ...(actions.suspend ? ["Suspendre"] : []),
    ...(actions.reactivate ? ["Réactiver"] : []),
    ...(actions.requestClosure ? ["Demander la fermeture"] : []),
  ];

  if (labels.length === 0) {
    return null;
  }

  return (
    <section aria-labelledby="store-actions-title" className="zandu-store-details__section">
      <h2 id="store-actions-title">Actions disponibles</h2>
      <ul className="zandu-store-details__actions">
        {labels.map((label) => (
          <li key={label}>
            {label === "Modifier" ? (
              <Link href={`/app/stores/${encodeURIComponent(storeId)}/edit`}>{label}</Link>
            ) : label === "Suspendre" && onSuspend !== undefined ? (
              <Button onClick={onSuspend} type="button">
                {label}
              </Button>
            ) : label === "Demander la fermeture" && onRequestClosure !== undefined ? (
              <Button onClick={onRequestClosure} type="button">
                {label}
              </Button>
            ) : label === "Annuler la fermeture" && onCancelClosure !== undefined ? (
              <Button onClick={onCancelClosure} type="button">
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
