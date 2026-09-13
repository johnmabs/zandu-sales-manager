"use client";

import { Button } from "@zandu/ui";
import Link from "next/link";
import { useState } from "react";

import { CancelInvitationDialog } from "./CancelInvitationDialog";

import type { CreatedInvitationResource } from "@zandu/api-client";

type InvitationSuccessStateProperties = Readonly<{
  canCancel?: boolean;
  copySecret?: (secret: string) => Promise<void>;
  cancelError?: unknown;
  invitation: CreatedInvitationResource;
  isCancelling?: boolean;
  onCancel?: (() => Promise<void>) | undefined;
}>;

/** Keeps the one-time invitation token in memory only for this mounted screen. */
export function InvitationSuccessState({
  canCancel = false,
  cancelError,
  copySecret = async (secret) => navigator.clipboard.writeText(secret),
  invitation,
  isCancelling = false,
  onCancel,
}: InvitationSuccessStateProperties) {
  const [copyState, setCopyState] = useState<"COPIED" | "IDLE" | "UNAVAILABLE">("IDLE");
  const [cancelled, setCancelled] = useState(false);
  const [cancelDialogOpen, setCancelDialogOpen] = useState(false);

  const copyToken = async () => {
    try {
      await copySecret(invitation.token);
      setCopyState("COPIED");
    } catch {
      setCopyState("UNAVAILABLE");
    }
  };

  return (
    <section aria-labelledby="invitation-success-title" className="zandu-invitation-success-state">
      <h1 id="invitation-success-title">Invitation créée</h1>
      <p>L’invitation pour {invitation.invitation.email} a été créée.</p>
      <dl>
        <div>
          <dt>Statut</dt>
          <dd>{invitation.invitation.status}</dd>
        </div>
        <div>
          <dt>Expiration</dt>
          <dd>{invitation.invitation.expiresAt}</dd>
        </div>
      </dl>
      <section aria-labelledby="invitation-token-title">
        <h2 id="invitation-token-title">Secret d’invitation</h2>
        <p role="alert">
          Ce secret n’est disponible qu’à présent. Copiez-le avant de quitter cet écran : il ne
          pourra peut-être pas être récupéré plus tard.
        </p>
        <code>{invitation.token}</code>
        <Button onClick={() => void copyToken()} type="button">
          Copier le secret
        </Button>
        {copyState === "COPIED" ? <p role="status">Secret copié.</p> : null}
        {copyState === "UNAVAILABLE" ? (
          <p role="alert">La copie automatique est indisponible. Copiez le secret manuellement.</p>
        ) : null}
      </section>
      {cancelled ? <p role="status">Invitation annulée.</p> : null}
      {!cancelled && canCancel && onCancel !== undefined ? (
        <Button onClick={() => setCancelDialogOpen(true)} type="button" variant="danger">
          Annuler l’invitation
        </Button>
      ) : null}
      <Link href="/app/access/invitations">Retour aux invitations</Link>
      <CancelInvitationDialog
        email={invitation.invitation.email}
        {...(cancelError === undefined ? {} : { error: cancelError })}
        isCancelling={isCancelling}
        onClose={() => setCancelDialogOpen(false)}
        onConfirm={() =>
          void onCancel?.().then(() => {
            setCancelled(true);
            setCancelDialogOpen(false);
          })
        }
        open={cancelDialogOpen}
      />
    </section>
  );
}
