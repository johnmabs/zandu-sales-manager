"use client";

import { ApiRequestError } from "@zandu/api-client";
import { ErrorMapper, type UiError } from "@zandu/error-contract";
import { useUnsavedChangesWarning, useZanduForm } from "@zandu/forms";
import { Button } from "@zandu/ui";
import { useId, useState } from "react";

import { hasUnknownInventoryMutationOutcome } from "../mutationSafety";

import { stockTransferCreateSchema, toStockTransferCreateInput } from "./stockTransferSchemas";

import type { StockTransferCreateFormValues } from "./stockTransferSchemas";
import type { StockTransferCreateInput } from "@zandu/api-client";
import type { AccessibleStore } from "@zandu/store-context";

const errorMapper = new ErrorMapper();

export function CreateStockTransferForm({
  defaultSourceStoreId,
  onCreate,
  stores,
}: Readonly<{
  defaultSourceStoreId: string;
  onCreate: (input: StockTransferCreateInput) => Promise<void>;
  stores: readonly AccessibleStore[];
}>) {
  const destinationErrorId = useId();
  const rootErrorId = useId();
  const sourceErrorId = useId();
  const [submissionError, setSubmissionError] = useState<UiError>();
  const [outcomeUnknown, setOutcomeUnknown] = useState(false);
  const form = useZanduForm<StockTransferCreateFormValues>(stockTransferCreateSchema, {
    defaultValues: { destinationStoreId: "", sourceStoreId: defaultSourceStoreId },
  });
  useUnsavedChangesWarning(form.formState.isDirty);

  const submit = form.handleSubmit(async (values) => {
    if (outcomeUnknown) return;
    form.clearErrors("root");
    setSubmissionError(undefined);
    try {
      await onCreate(toStockTransferCreateInput(values));
    } catch (error: unknown) {
      const mapped = mapTransferMutationError(error, "création");
      setSubmissionError(mapped);
      setOutcomeUnknown(hasUnknownInventoryMutationOutcome(error));
      for (const field of ["sourceStoreId", "destinationStoreId"] as const) {
        const message = mapped.fieldErrors?.[field]?.[0];
        if (message !== undefined) form.setError(field, { message, type: "server" });
      }
      form.setError("root", { message: mapped.message, type: "server" });
    }
  });

  return (
    <form aria-label="Créer un transfert de stock" noValidate onSubmit={submit}>
      <p>Les deux magasins doivent être opérationnels, accessibles et distincts.</p>
      <label>
        Magasin source
        <select
          {...form.register("sourceStoreId")}
          aria-describedby={form.formState.errors.sourceStoreId ? sourceErrorId : undefined}
          aria-invalid={form.formState.errors.sourceStoreId ? true : undefined}
        >
          <option value="">Sélectionner</option>
          {stores.map((store) => (
            <option key={store.id} value={store.id}>
              {store.name}
            </option>
          ))}
        </select>
      </label>
      <MutationFieldError
        id={sourceErrorId}
        message={form.formState.errors.sourceStoreId?.message}
      />
      <label>
        Magasin destination
        <select
          {...form.register("destinationStoreId")}
          aria-describedby={
            form.formState.errors.destinationStoreId ? destinationErrorId : undefined
          }
          aria-invalid={form.formState.errors.destinationStoreId ? true : undefined}
        >
          <option value="">Sélectionner</option>
          {stores.map((store) => (
            <option key={store.id} value={store.id}>
              {store.name}
            </option>
          ))}
        </select>
      </label>
      <MutationFieldError
        id={destinationErrorId}
        message={form.formState.errors.destinationStoreId?.message}
      />
      <MutationFieldError
        correlationId={submissionError?.correlationId}
        id={rootErrorId}
        message={form.formState.errors.root?.message}
      />
      <Button disabled={form.formState.isSubmitting || outcomeUnknown} type="submit">
        {form.formState.isSubmitting
          ? "Création en cours"
          : outcomeUnknown
            ? "Résultat à vérifier"
            : "Créer le transfert"}
      </Button>
    </form>
  );
}

export function mapTransferMutationError(error: unknown, operation: string): UiError {
  if (hasUnknownInventoryMutationOutcome(error)) {
    const mapped = errorMapper.map(
      error instanceof ApiRequestError ? error.apiError : { kind: "response", status: 500 },
    );
    return {
      ...(mapped.correlationId === undefined ? {} : { correlationId: mapped.correlationId }),
      message: `La ${operation} a peut-être été enregistrée. Rechargez le transfert avant toute nouvelle tentative.`,
      retryable: false,
      title: "Résultat à vérifier",
    };
  }
  return errorMapper.map(
    error instanceof ApiRequestError ? error.apiError : { kind: "response", status: 500 },
    {
      CONFLICT: {
        message: "Le transfert a été modifié ailleurs. Rechargez-le avant de recommencer.",
        retryable: false,
        title: "Transfert en conflit",
      },
      DOMAIN_RULE_VIOLATION: {
        message: "Le transfert ou sa ligne ne respecte pas les règles Inventory.",
        retryable: false,
        title: "Modification impossible",
      },
      VALIDATION_ERROR: {
        message: "Les données du transfert sont invalides.",
        retryable: false,
        title: "Données invalides",
      },
    },
  );
}

export function MutationFieldError({
  correlationId,
  id,
  message,
}: Readonly<{
  correlationId?: string | undefined;
  id: string;
  message?: string | undefined;
}>) {
  return message === undefined ? null : (
    <div id={id} role="alert">
      <p>{message}</p>
      {correlationId === undefined ? null : <p>Référence de diagnostic : {correlationId}</p>}
    </div>
  );
}
