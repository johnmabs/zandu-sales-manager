"use client";

import { ApiRequestError } from "@zandu/api-client";
import { ErrorMapper, type UiError } from "@zandu/error-contract";
import { useUnsavedChangesWarning, useZanduForm } from "@zandu/forms";
import { Button, Input } from "@zandu/ui";
import { useId, useState } from "react";

import { hasUnknownInventoryMutationOutcome } from "../mutationSafety";

import { initializeStockSchema, toStockInitializeInput } from "./initializeStockSchema";

import type { InitializeStockFormValues } from "./initializeStockSchema";
import type { StockInitializeInput } from "@zandu/api-client";

const errorMapper = new ErrorMapper();

export function InitializeStockForm({
  currency,
  onInitialize,
  productLabel,
}: Readonly<{
  currency: string;
  onInitialize: (input: StockInitializeInput) => Promise<void>;
  productLabel: string;
}>) {
  const quantityErrorId = useId();
  const unitCostErrorId = useId();
  const rootErrorId = useId();
  const [submissionError, setSubmissionError] = useState<UiError>();
  const [outcomeUnknown, setOutcomeUnknown] = useState(false);
  const form = useZanduForm<InitializeStockFormValues>(initializeStockSchema, {
    defaultValues: { quantity: "", unitCost: "" },
  });
  useUnsavedChangesWarning(form.formState.isDirty);

  const submit = form.handleSubmit(async (values) => {
    if (outcomeUnknown) return;
    const input = toStockInitializeInput(values);
    if (
      !window.confirm(
        `Initialiser ${productLabel} avec ${input.quantity} unité(s) au coût unitaire ${input.unitCost} ${currency} ? Cette opération est unique.`,
      )
    ) {
      return;
    }
    form.clearErrors("root");
    setSubmissionError(undefined);
    try {
      await onInitialize(input);
    } catch (error: unknown) {
      setSubmissionError(applyInitializeStockErrors(form, error));
      setOutcomeUnknown(hasUnknownInventoryMutationOutcome(error));
    }
  });

  return (
    <form aria-label="Initialiser la position de stock" noValidate onSubmit={submit}>
      <p>
        Cette opération crée la quantité physique et sa valorisation initiale. Elle ne peut être
        effectuée qu’une fois.
      </p>
      <label>
        Quantité initiale
        <Input
          {...form.register("quantity")}
          aria-describedby={form.formState.errors.quantity ? quantityErrorId : undefined}
          aria-invalid={form.formState.errors.quantity ? true : undefined}
          inputMode="decimal"
        />
      </label>
      <FieldError id={quantityErrorId} message={form.formState.errors.quantity?.message} />
      <label>
        Coût unitaire d’ouverture ({currency})
        <Input
          {...form.register("unitCost")}
          aria-describedby={form.formState.errors.unitCost ? unitCostErrorId : undefined}
          aria-invalid={form.formState.errors.unitCost ? true : undefined}
          inputMode="decimal"
        />
      </label>
      <FieldError id={unitCostErrorId} message={form.formState.errors.unitCost?.message} />
      <FieldError
        {...(submissionError?.correlationId === undefined
          ? {}
          : { correlationId: submissionError.correlationId })}
        id={rootErrorId}
        message={form.formState.errors.root?.message}
      />
      <Button disabled={form.formState.isSubmitting || outcomeUnknown} type="submit">
        {form.formState.isSubmitting
          ? "Initialisation en cours"
          : outcomeUnknown
            ? "Résultat à vérifier"
            : "Initialiser le stock"}
      </Button>
    </form>
  );
}

function applyInitializeStockErrors(
  form: ReturnType<typeof useZanduForm<InitializeStockFormValues>>,
  error: unknown,
): UiError {
  if (hasUnknownInventoryMutationOutcome(error)) {
    const mapped = errorMapper.map(
      error instanceof ApiRequestError ? error.apiError : { kind: "response", status: 500 },
    );
    const result: UiError = {
      ...(mapped.correlationId === undefined ? {} : { correlationId: mapped.correlationId }),
      message:
        "L’initialisation a peut-être été enregistrée. Vérifiez la position avant toute nouvelle tentative.",
      retryable: false,
      title: "Résultat d’initialisation à vérifier",
    };
    form.setError("root", { message: result.message, type: "unknown-outcome" });
    return result;
  }

  const mapped = errorMapper.map(
    error instanceof ApiRequestError ? error.apiError : { kind: "response", status: 500 },
    {
      CONFLICT: {
        message: "Cette position de stock est déjà initialisée.",
        retryable: false,
        title: "Position déjà initialisée",
      },
      DOMAIN_RULE_VIOLATION: {
        message:
          "Le produit, la quantité ou le coût d’ouverture ne respecte pas les règles Inventory.",
        retryable: false,
        title: "Initialisation impossible",
      },
    },
  );
  if (mapped.fieldErrors !== undefined) {
    for (const field of ["quantity", "unitCost"] as const) {
      const message = mapped.fieldErrors[field]?.[0];
      if (message !== undefined) form.setError(field, { message, type: "server" });
    }
  }
  form.setError("root", { message: mapped.message, type: "server" });
  return mapped;
}

function FieldError({
  correlationId,
  id,
  message,
}: Readonly<{
  correlationId?: string;
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
