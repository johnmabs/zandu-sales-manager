"use client";

import { ApiRequestError } from "@zandu/api-client";
import { ErrorMapper, type UiError } from "@zandu/error-contract";
import { Alert, Button, Dialog, Textarea } from "@zandu/ui";
import { useState } from "react";

type RequestStoreClosureDialogProperties = Readonly<{
  error?: unknown;
  isRequesting: boolean;
  onClose: () => void;
  onConfirm: (reason: string) => void;
  open: boolean;
  storeName: string;
}>;

const errorMapper = new ErrorMapper();

export function RequestStoreClosureDialog({
  error,
  isRequesting,
  onClose,
  onConfirm,
  open,
  storeName,
}: RequestStoreClosureDialogProperties) {
  const [reason, setReason] = useState("");
  const normalizedReason = reason.trim();
  const errorPresentation = error === undefined ? undefined : closureErrorPresentation(error);

  return (
    <Dialog
      onClose={isRequesting ? () => undefined : onClose}
      open={open}
      title="Demander la fermeture du magasin"
    >
      <p>La fermeture de {storeName} sera traitée comme un workflow distinct et contrôlé.</p>
      <label>
        Motif de fermeture
        <Textarea
          disabled={isRequesting}
          onChange={(event) => setReason(event.target.value)}
          required
          value={reason}
        />
      </label>
      {errorPresentation === undefined ? null : (
        <Alert tone="danger">
          <p>{errorPresentation.message}</p>
          {errorPresentation.correlationId === undefined ? null : (
            <p>Référence de diagnostic : {errorPresentation.correlationId}</p>
          )}
        </Alert>
      )}
      <Button disabled={isRequesting} onClick={onClose} type="button">
        Annuler
      </Button>
      <Button
        disabled={isRequesting || normalizedReason.length === 0}
        onClick={() => onConfirm(normalizedReason)}
        type="button"
      >
        {isRequesting ? "Demande en cours" : "Confirmer la demande de fermeture"}
      </Button>
    </Dialog>
  );
}

function closureErrorPresentation(error: unknown): UiError {
  if (error instanceof ApiRequestError) {
    return errorMapper.map(error.apiError, {
      DOMAIN_RULE_VIOLATION: {
        message: "La fermeture ne peut pas être demandée dans l’état actuel du magasin.",
        retryable: false,
        title: "Demande impossible",
      },
    });
  }

  return errorMapper.map({ kind: "response", status: 500 });
}
