"use client";

import { ApiRequestError } from "@zandu/api-client";
import { ErrorMapper, type UiError } from "@zandu/error-contract";
import { useUnsavedChangesWarning, useZanduForm } from "@zandu/forms";
import { Button, Input } from "@zandu/ui";
import { useId, useState } from "react";

import { hasUnknownInventoryMutationOutcome } from "../mutationSafety";

import {
  adjustStockSchema,
  isIncomingStockAdjustment,
  toStockAdjustInput,
} from "./adjustStockSchema";

import type { AdjustStockFormValues } from "./adjustStockSchema";
import type { StockAdjustInput } from "@zandu/api-client";

const errorMapper = new ErrorMapper();

export function AdjustStockForm({
  currency,
  onAdjust,
}: Readonly<{
  currency: string;
  onAdjust: (input: StockAdjustInput) => Promise<void>;
}>) {
  const deltaErrorId = useId();
  const reasonErrorId = useId();
  const unitCostErrorId = useId();
  const rootErrorId = useId();
  const [submissionError, setSubmissionError] = useState<UiError>();
  const [outcomeUnknown, setOutcomeUnknown] = useState(false);
  const form = useZanduForm<AdjustStockFormValues>(adjustStockSchema, {
    defaultValues: { delta: "", reason: "", unitCost: "" },
  });
  useUnsavedChangesWarning(form.formState.isDirty);
  const incoming = isIncomingStockAdjustment(form.watch("delta"));

  const submit = form.handleSubmit(async (values) => {
    if (outcomeUnknown) return;
    form.clearErrors("root");
    setSubmissionError(undefined);
    try {
      await onAdjust(toStockAdjustInput(values));
      form.reset();
    } catch (error: unknown) {
      setSubmissionError(applyAdjustStockErrors(form, error));
      setOutcomeUnknown(hasUnknownInventoryMutationOutcome(error));
    }
  });

  return (
    <form aria-label="Ajuster la position de stock" noValidate onSubmit={submit}>
      <p>
        Utilisez un delta positif pour une entrée et un delta négatif pour une sortie. La quantité
        finale reste contrôlée par le serveur.
      </p>
      <label>
        Delta de quantité
        <Input
          {...form.register("delta")}
          aria-describedby={form.formState.errors.delta ? deltaErrorId : undefined}
          aria-invalid={form.formState.errors.delta ? true : undefined}
          inputMode="decimal"
        />
      </label>
      <FieldError id={deltaErrorId} message={form.formState.errors.delta?.message} />
      <label>
        Raison de l’ajustement
        <Input
          {...form.register("reason")}
          aria-describedby={form.formState.errors.reason ? reasonErrorId : undefined}
          aria-invalid={form.formState.errors.reason ? true : undefined}
        />
      </label>
      <FieldError id={reasonErrorId} message={form.formState.errors.reason?.message} />
      {incoming ? (
        <>
          <label>
            Coût unitaire de l’entrée ({currency})
            <Input
              {...form.register("unitCost")}
              aria-describedby={form.formState.errors.unitCost ? unitCostErrorId : undefined}
              aria-invalid={form.formState.errors.unitCost ? true : undefined}
              inputMode="decimal"
            />
          </label>
          <FieldError id={unitCostErrorId} message={form.formState.errors.unitCost?.message} />
        </>
      ) : null}
      <FieldError
        {...(submissionError?.correlationId === undefined
          ? {}
          : { correlationId: submissionError.correlationId })}
        id={rootErrorId}
        message={form.formState.errors.root?.message}
      />
      <Button disabled={form.formState.isSubmitting || outcomeUnknown} type="submit">
        {form.formState.isSubmitting
          ? "Ajustement en cours"
          : outcomeUnknown
            ? "Résultat à vérifier"
            : "Ajuster le stock"}
      </Button>
    </form>
  );
}

function applyAdjustStockErrors(
  form: ReturnType<typeof useZanduForm<AdjustStockFormValues>>,
  error: unknown,
): UiError {
  if (hasUnknownInventoryMutationOutcome(error)) {
    const mapped = errorMapper.map(
      error instanceof ApiRequestError ? error.apiError : { kind: "response", status: 500 },
    );
    const result: UiError = {
      ...(mapped.correlationId === undefined ? {} : { correlationId: mapped.correlationId }),
      message:
        "L’ajustement a peut-être été enregistré. Vérifiez la position avant toute nouvelle tentative.",
      retryable: false,
      title: "Résultat d’ajustement à vérifier",
    };
    form.setError("root", { message: result.message, type: "unknown-outcome" });
    return result;
  }

  const mapped = errorMapper.map(
    error instanceof ApiRequestError ? error.apiError : { kind: "response", status: 500 },
    {
      CONFLICT: {
        message: "La position est en cours de modification. Rechargez-la avant de recommencer.",
        retryable: false,
        title: "Ajustement en conflit",
      },
      DOMAIN_RULE_VIOLATION: {
        message: "La position doit être initialisée avant tout ajustement.",
        retryable: false,
        title: "Ajustement impossible",
      },
      STOCK_COUNT_PRODUCT_LOCKED: {
        message: "Ce produit est verrouillé par un inventaire physique ouvert.",
        retryable: false,
        title: "Produit verrouillé",
      },
      VALIDATION_ERROR: {
        message: "L’ajustement est invalide ou la sortie dépasse le stock disponible.",
        retryable: false,
        title: "Ajustement refusé",
      },
      VALUATION_UNIT_COST_REQUIRED: {
        message: "Un coût unitaire est requis pour cette entrée de stock.",
        retryable: false,
        title: "Coût requis",
      },
      VALUATION_UNIT_COST_UNEXPECTED: {
        message: "Aucun coût unitaire ne doit être envoyé pour une sortie de stock.",
        retryable: false,
        title: "Coût inattendu",
      },
    },
  );
  if (mapped.fieldErrors !== undefined) {
    for (const field of ["delta", "reason", "unitCost"] as const) {
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
