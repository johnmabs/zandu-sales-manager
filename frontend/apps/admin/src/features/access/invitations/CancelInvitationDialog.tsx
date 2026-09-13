"use client";

import { ApiRequestError } from "@zandu/api-client";
import { ErrorMapper } from "@zandu/error-contract";
import { Alert, Button, Dialog } from "@zandu/ui";
import { useId } from "react";

const errorMapper = new ErrorMapper();

export function CancelInvitationDialog({
  email,
  error,
  isCancelling,
  onClose,
  onConfirm,
  open,
}: Readonly<{
  email: string;
  error?: unknown;
  isCancelling: boolean;
  onClose: () => void;
  onConfirm: () => void;
  open: boolean;
}>) {
  const descriptionId = useId();
  const mapped =
    error === undefined
      ? undefined
      : errorMapper.map(
          error instanceof ApiRequestError ? error.apiError : { kind: "response", status: 500 },
        );

  return (
    <Dialog
      descriptionId={descriptionId}
      onClose={isCancelling ? () => undefined : onClose}
      open={open}
      title="Annuler l’invitation"
    >
      <p id={descriptionId}>
        Annuler l’invitation de {email} empêchera définitivement son acceptation avec ce secret.
      </p>
      {mapped === undefined ? null : (
        <Alert tone="danger">
          <p>{mapped.message}</p>
          {mapped.correlationId === undefined ? null : (
            <p>Référence de diagnostic : {mapped.correlationId}</p>
          )}
        </Alert>
      )}
      <Button disabled={isCancelling} onClick={onClose} type="button" variant="secondary">
        Conserver l’invitation
      </Button>
      <Button disabled={isCancelling} onClick={onConfirm} type="button" variant="danger">
        {isCancelling ? "Annulation en cours" : "Confirmer l’annulation"}
      </Button>
    </Dialog>
  );
}
