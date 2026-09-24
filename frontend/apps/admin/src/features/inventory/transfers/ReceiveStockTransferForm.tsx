"use client";

import { ApiRequestError, CriticalMutationManager } from "@zandu/api-client";
import { ErrorMapper, type UiError } from "@zandu/error-contract";
import { useUnsavedChangesWarning, useZanduForm } from "@zandu/forms";
import { Button, Input } from "@zandu/ui";
import { useId, useRef, useState } from "react";

import { hasUnknownInventoryMutationOutcome } from "../mutationSafety";

import { MutationFieldError } from "./CreateStockTransferForm";
import { stockTransferReceiveSchema, toStockTransferReceiveInput } from "./stockTransferSchemas";

import type { StockTransferReceiveFormValues } from "./stockTransferSchemas";
import type { StockTransferLineResource, StockTransferReceiveInput } from "@zandu/api-client";

const errorMapper = new ErrorMapper();

export function ReceiveStockTransferForm({
  lines,
  onReceive,
  productNames = new Map(),
  transferId,
}: Readonly<{
  lines: readonly StockTransferLineResource[];
  onReceive: (input: StockTransferReceiveInput, idempotencyKey: string) => Promise<void>;
  productNames?: ReadonlyMap<string, string>;
  transferId: string;
}>) {
  const rootErrorId = useId();
  const [submissionError, setSubmissionError] = useState<UiError>();
  const [outcomeUnknown, setOutcomeUnknown] = useState(false);
  const mutationManager = useRef(new CriticalMutationManager());
  const intentId = `receive-stock-transfer:${transferId}`;
  const form = useZanduForm<StockTransferReceiveFormValues>(stockTransferReceiveSchema, {
    defaultValues: {
      lines: lines.map((line) => ({
        lineId: line.id,
        receivedQuantity: line.shippedQuantity ?? "",
      })),
    },
  });
  useUnsavedChangesWarning(form.formState.isDirty);

  const submit = form.handleSubmit(async (values) => {
    if (
      !window.confirm(
        "Réceptionner ce transfert ? Seules les quantités réellement reçues seront ajoutées au magasin destination.",
      )
    ) {
      return;
    }
    form.clearErrors("root");
    setSubmissionError(undefined);
    const attempt = mutationManager.current.begin(intentId);
    try {
      await onReceive(toStockTransferReceiveInput(values), attempt.key!);
      mutationManager.current.completeSuccess(intentId);
      setOutcomeUnknown(false);
    } catch (error: unknown) {
      if (hasUnknownInventoryMutationOutcome(error)) {
        mutationManager.current.markUnknownOutcome(intentId);
        setOutcomeUnknown(true);
      } else {
        if (error instanceof ApiRequestError && error.apiError.kind === "response") {
          mutationManager.current.completeBusinessFailure(intentId);
        } else {
          mutationManager.current.completeTechnicalFailure(intentId);
        }
        mutationManager.current.retire(intentId);
        setOutcomeUnknown(false);
      }
      const mapped = mapReceptionError(error);
      setSubmissionError(mapped);
      form.setError("root", { message: mapped.message, type: "server" });
    }
  });

  return (
    <section aria-labelledby="receive-stock-transfer">
      <h3 id="receive-stock-transfer">Réceptionner le transfert</h3>
      <p>
        Saisissez les quantités physiquement reçues. Une valeur nulle est acceptée ; toute quantité
        manquante restera un écart de transit calculé par le serveur et ne sera jamais ajoutée au
        stock.
      </p>
      <form aria-label="Réceptionner le transfert" noValidate onSubmit={submit}>
        {lines.map((line, index) => {
          const error = form.formState.errors.lines?.[index]?.receivedQuantity;
          const errorId = `${rootErrorId}-line-${index}`;
          return (
            <div key={line.id}>
              <input type="hidden" {...form.register(`lines.${index}.lineId`)} />
              <label>
                Quantité reçue — {productNames.get(line.productId) ?? line.productId}
                <Input
                  {...form.register(`lines.${index}.receivedQuantity`)}
                  aria-describedby={error ? errorId : undefined}
                  aria-invalid={error ? true : undefined}
                  inputMode="decimal"
                />
              </label>
              <p>Quantité expédiée : {line.shippedQuantity ?? "—"}</p>
              <MutationFieldError id={errorId} message={error?.message} />
            </div>
          );
        })}
        <MutationFieldError
          correlationId={submissionError?.correlationId}
          id={rootErrorId}
          message={form.formState.errors.root?.message}
        />
        <Button disabled={form.formState.isSubmitting} type="submit">
          {form.formState.isSubmitting
            ? "Réception en cours"
            : outcomeUnknown
              ? "Réessayer avec la même clé"
              : "Confirmer la réception"}
        </Button>
      </form>
    </section>
  );
}

function mapReceptionError(error: unknown): UiError {
  if (hasUnknownInventoryMutationOutcome(error)) {
    const mapped = errorMapper.map(
      error instanceof ApiRequestError ? error.apiError : { kind: "response", status: 500 },
    );
    return {
      ...(mapped.correlationId === undefined ? {} : { correlationId: mapped.correlationId }),
      message:
        "La réception a peut-être été enregistrée. Le statut et les stocks sont actualisés ; si le transfert reste expédié, réessayez explicitement avec la même clé.",
      retryable: true,
      title: "Résultat à vérifier",
    };
  }
  return errorMapper.map(
    error instanceof ApiRequestError ? error.apiError : { kind: "response", status: 500 },
    {
      STOCK_TRANSFER_CONFLICT: {
        message: "La réception concurrente est incomplète. Rechargez le transfert.",
        retryable: false,
        title: "Transfert en conflit",
      },
      STOCK_TRANSFER_NOT_SHIPPED: {
        message: "Seul un transfert expédié peut être réceptionné.",
        retryable: false,
        title: "Réception impossible",
      },
      TRANSFER_RECEIVED_QUANTITY_EXCEEDS_SHIPPED: {
        message: "Une quantité reçue dépasse la quantité expédiée.",
        retryable: false,
        title: "Quantité invalide",
      },
    },
  );
}
