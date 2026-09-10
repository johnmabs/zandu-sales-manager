"use client";

import { ApiRequestError } from "@zandu/api-client";
import { ErrorMapper, type UiError } from "@zandu/error-contract";
import { Alert, Button, Dialog } from "@zandu/ui";

import { hasUnknownStoreMutationOutcome } from "../mutationSafety";

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
  const outcomeUnknown = hasUnknownStoreMutationOutcome(error);

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
      <Button disabled={isSuspending || outcomeUnknown} onClick={onConfirm} type="button">
        {isSuspending
          ? "Suspension en cours"
          : outcomeUnknown
            ? "Résultat à vérifier"
            : "Confirmer la suspension"}
      </Button>
    </Dialog>
  );
}

function suspendErrorPresentation(error: unknown): UiError {
  if (hasUnknownStoreMutationOutcome(error)) {
    const mapped = errorMapper.map(
      error instanceof ApiRequestError ? error.apiError : { kind: "response", status: 500 },
    );
    return {
      ...(mapped.correlationId === undefined ? {} : { correlationId: mapped.correlationId }),
      message:
        "La suspension a peut-être été enregistrée. Vérifiez l’état du magasin avant de recommencer.",
      retryable: false,
      title: "Résultat de suspension à vérifier",
    };
  }
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
