"use client";

import { ApiRequestError } from "@zandu/api-client";
import { ErrorMapper, type UiError } from "@zandu/error-contract";
import { Alert, Button, Dialog } from "@zandu/ui";

type CancelStoreClosureDialogProperties = Readonly<{
  error?: unknown;
  isCancelling: boolean;
  onClose: () => void;
  onConfirm: () => void;
  open: boolean;
  storeName: string;
}>;

const errorMapper = new ErrorMapper();

export function CancelStoreClosureDialog({
  error,
  isCancelling,
  onClose,
  onConfirm,
  open,
  storeName,
}: CancelStoreClosureDialogProperties) {
  const errorPresentation = error === undefined ? undefined : cancellationErrorPresentation(error);

  return (
    <Dialog
      onClose={isCancelling ? () => undefined : onClose}
      open={open}
      title="Annuler la fermeture du magasin"
    >
      <p>La demande de fermeture de {storeName} sera annulée et le magasin redeviendra actif.</p>
      {errorPresentation === undefined ? null : (
        <Alert tone="danger">
          <p>{errorPresentation.message}</p>
          {errorPresentation.correlationId === undefined ? null : (
            <p>Référence de diagnostic : {errorPresentation.correlationId}</p>
          )}
        </Alert>
      )}
      <Button disabled={isCancelling} onClick={onClose} type="button">
        Retour
      </Button>
      <Button disabled={isCancelling} onClick={onConfirm} type="button">
        {isCancelling ? "Annulation en cours" : "Confirmer l’annulation"}
      </Button>
    </Dialog>
  );
}

function cancellationErrorPresentation(error: unknown): UiError {
  if (error instanceof ApiRequestError) {
    return errorMapper.map(error.apiError, {
      DOMAIN_RULE_VIOLATION: {
        message: "Cette demande de fermeture ne peut plus être annulée.",
        retryable: false,
        title: "Annulation impossible",
      },
    });
  }

  return errorMapper.map({ kind: "response", status: 500 });
}
