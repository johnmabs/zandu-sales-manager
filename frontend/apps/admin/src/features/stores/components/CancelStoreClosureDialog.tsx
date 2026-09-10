"use client";

import { ApiRequestError } from "@zandu/api-client";
import { ErrorMapper, type UiError } from "@zandu/error-contract";
import { Alert, Button, Dialog } from "@zandu/ui";
import { useId } from "react";

import { hasUnknownStoreMutationOutcome } from "../mutationSafety";

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
  const descriptionId = useId();
  const errorPresentation = error === undefined ? undefined : cancellationErrorPresentation(error);
  const outcomeUnknown = hasUnknownStoreMutationOutcome(error);

  return (
    <Dialog
      closeLabel="Fermer le dialogue"
      descriptionId={descriptionId}
      onClose={isCancelling ? () => undefined : onClose}
      open={open}
      title="Annuler la fermeture du magasin"
    >
      <p id={descriptionId}>
        La demande de fermeture de {storeName} sera annulée et le magasin redeviendra actif.
      </p>
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
      <Button disabled={isCancelling || outcomeUnknown} onClick={onConfirm} type="button">
        {isCancelling
          ? "Annulation en cours"
          : outcomeUnknown
            ? "Résultat à vérifier"
            : "Confirmer l’annulation"}
      </Button>
    </Dialog>
  );
}

function cancellationErrorPresentation(error: unknown): UiError {
  if (hasUnknownStoreMutationOutcome(error)) {
    const mapped = errorMapper.map(
      error instanceof ApiRequestError ? error.apiError : { kind: "response", status: 500 },
    );
    return {
      ...(mapped.correlationId === undefined ? {} : { correlationId: mapped.correlationId }),
      message:
        "L’annulation a peut-être été enregistrée. Vérifiez l’état du magasin avant de recommencer.",
      retryable: false,
      title: "Résultat d’annulation à vérifier",
    };
  }
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
