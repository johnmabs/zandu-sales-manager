"use client";

import { ApiRequestError } from "@zandu/api-client";
import { ErrorMapper, type UiError } from "@zandu/error-contract";
import { useUnsavedChangesWarning, useZanduForm } from "@zandu/forms";
import { Button, Textarea } from "@zandu/ui";
import { useId, useState } from "react";

import { hasUnknownInventoryMutationOutcome } from "../mutationSafety";

import { MutationFieldError } from "./CreateStockTransferForm";
import { stockTransferCancelSchema, toStockTransferCancelInput } from "./stockTransferSchemas";

import type { StockTransferCancelFormValues } from "./stockTransferSchemas";
import type { StockTransferCancelInput } from "@zandu/api-client";

const errorMapper = new ErrorMapper();

export function CancelStockTransferForm({
  onCancel,
}: Readonly<{
  onCancel: (input: StockTransferCancelInput) => Promise<void>;
}>) {
  const reasonErrorId = useId();
  const rootErrorId = useId();
  const [submissionError, setSubmissionError] = useState<UiError>();
  const [outcomeUnknown, setOutcomeUnknown] = useState(false);
  const form = useZanduForm<StockTransferCancelFormValues>(stockTransferCancelSchema, {
    defaultValues: { reason: "" },
  });
  useUnsavedChangesWarning(form.formState.isDirty);

  const submit = form.handleSubmit(async (values) => {
    if (outcomeUnknown) return;
    if (!window.confirm("Annuler ce transfert ? Cette action est définitive.")) return;
    form.clearErrors("root");
    setSubmissionError(undefined);
    try {
      await onCancel(toStockTransferCancelInput(values));
    } catch (error: unknown) {
      const mapped = mapCancellationError(error);
      setSubmissionError(mapped);
      setOutcomeUnknown(hasUnknownInventoryMutationOutcome(error));
      const reasonMessage = mapped.fieldErrors?.reason?.[0];
      if (reasonMessage !== undefined) {
        form.setError("reason", { message: reasonMessage, type: "server" });
      }
      form.setError("root", { message: mapped.message, type: "server" });
    }
  });

  return (
    <section aria-labelledby="cancel-stock-transfer">
      <h3 id="cancel-stock-transfer">Annuler le transfert</h3>
      <p>Seul un brouillon peut être annulé. Le motif sera conservé dans le document final.</p>
      <form aria-label="Annuler le transfert" noValidate onSubmit={submit}>
        <label>
          Motif d’annulation
          <Textarea
            {...form.register("reason")}
            aria-describedby={form.formState.errors.reason ? reasonErrorId : undefined}
            aria-invalid={form.formState.errors.reason ? true : undefined}
          />
        </label>
        <MutationFieldError id={reasonErrorId} message={form.formState.errors.reason?.message} />
        <MutationFieldError
          correlationId={submissionError?.correlationId}
          id={rootErrorId}
          message={form.formState.errors.root?.message}
        />
        <Button disabled={form.formState.isSubmitting || outcomeUnknown} type="submit">
          {form.formState.isSubmitting
            ? "Annulation en cours"
            : outcomeUnknown
              ? "Résultat à vérifier"
              : "Confirmer l’annulation"}
        </Button>
      </form>
    </section>
  );
}

function mapCancellationError(error: unknown): UiError {
  if (hasUnknownInventoryMutationOutcome(error)) {
    const mapped = errorMapper.map(
      error instanceof ApiRequestError ? error.apiError : { kind: "response", status: 500 },
    );
    return {
      ...(mapped.correlationId === undefined ? {} : { correlationId: mapped.correlationId }),
      message:
        "L’annulation a peut-être été enregistrée. Le transfert est actualisé ; vérifiez son statut avant toute nouvelle tentative.",
      retryable: false,
      title: "Résultat à vérifier",
    };
  }
  return errorMapper.map(
    error instanceof ApiRequestError ? error.apiError : { kind: "response", status: 500 },
    {
      STOCK_TRANSFER_CANCELLATION_REASON_INVALID: {
        message: "Le motif d’annulation doit contenir entre 1 et 500 caractères.",
        retryable: false,
        title: "Motif invalide",
      },
      STOCK_TRANSFER_NOT_EDITABLE: {
        message: "Seul un transfert en brouillon peut être annulé.",
        retryable: false,
        title: "Annulation impossible",
      },
    },
  );
}
