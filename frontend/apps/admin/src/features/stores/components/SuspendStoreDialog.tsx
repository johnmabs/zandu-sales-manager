"use client";

import { ApiRequestError } from "@zandu/api-client";
import { ErrorMapper, type UiError } from "@zandu/error-contract";
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
  const errorPresentation = error === undefined ? undefined : suspendErrorPresentation(error);

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
      {errorPresentation === undefined ? null : (
        <Alert tone="danger">
          <p>{errorPresentation.message}</p>
          {errorPresentation.correlationId === undefined ? null : (
            <p>Référence de diagnostic : {errorPresentation.correlationId}</p>
          )}
        </Alert>
      )}
      <Button disabled={isSuspending} onClick={onClose} type="button">
        Annuler
      </Button>
      <Button disabled={isSuspending} onClick={onConfirm} type="button">
        {isSuspending ? "Suspension en cours" : "Confirmer la suspension"}
      </Button>
    </Dialog>
  );
}

function suspendErrorPresentation(error: unknown): UiError {
  if (error instanceof ApiRequestError) {
    return errorMapper.map(error.apiError, {
      DOMAIN_RULE_VIOLATION: {
        message: "La suspension est indisponible dans l’état actuel du magasin.",
        retryable: false,
        title: "Suspension impossible",
      },
    });
  }

  return errorMapper.map({ kind: "response", status: 500 });
}
