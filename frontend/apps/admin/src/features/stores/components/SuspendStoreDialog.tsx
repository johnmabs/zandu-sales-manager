"use client";

import { ApiRequestError } from "@zandu/api-client";
import { ErrorMapper } from "@zandu/error-contract";
import { Alert, Button, Dialog } from "@zandu/ui";

type SuspendStoreDialogProperties = Readonly<{
  error?: unknown;
  isSuspending: boolean;
  onClose: () => void;
  onConfirm: () => void;
  open: boolean;
  storeName: string;
}>;

const errorMapper = new ErrorMapper();

export function SuspendStoreDialog({
  error,
  isSuspending,
  onClose,
  onConfirm,
  open,
  storeName,
}: SuspendStoreDialogProperties) {
  return (
    <Dialog
      onClose={isSuspending ? () => undefined : onClose}
      open={open}
      title="Suspendre le magasin"
    >
      <p>
        Suspendre {storeName} bloquera les nouvelles opérations de ce magasin jusqu’à sa
        réactivation.
      </p>
      {error === undefined ? null : <Alert tone="danger">{suspendErrorMessage(error)}</Alert>}
      <Button disabled={isSuspending} onClick={onClose} type="button">
        Annuler
      </Button>
      <Button disabled={isSuspending} onClick={onConfirm} type="button">
        {isSuspending ? "Suspension en cours" : "Confirmer la suspension"}
      </Button>
    </Dialog>
  );
}

function suspendErrorMessage(error: unknown): string {
  if (error instanceof ApiRequestError) {
    return errorMapper.map(error.apiError, {
      DOMAIN_RULE_VIOLATION: {
        message: "La suspension est indisponible dans l’état actuel du magasin.",
        retryable: false,
        title: "Suspension impossible",
      },
    }).message;
  }

  return "Une erreur inattendue est survenue. Réessayez ultérieurement.";
}
