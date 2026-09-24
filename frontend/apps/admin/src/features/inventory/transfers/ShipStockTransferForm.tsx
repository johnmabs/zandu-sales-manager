"use client";

import { ApiRequestError, CriticalMutationManager } from "@zandu/api-client";
import { ErrorMapper, type UiError } from "@zandu/error-contract";
import { useUnsavedChangesWarning, useZanduForm } from "@zandu/forms";
import { Button, Input } from "@zandu/ui";
import { useId, useRef, useState } from "react";

import { hasUnknownInventoryMutationOutcome } from "../mutationSafety";

import { MutationFieldError } from "./CreateStockTransferForm";
import { stockTransferShipSchema, toStockTransferShipInput } from "./stockTransferSchemas";

import type { StockTransferShipFormValues } from "./stockTransferSchemas";
import type { StockTransferLineResource, StockTransferShipInput } from "@zandu/api-client";

const errorMapper = new ErrorMapper();

export function ShipStockTransferForm({
  lines,
  onShip,
  productNames = new Map(),
  transferId,
}: Readonly<{
  lines: readonly StockTransferLineResource[];
  onShip: (input: StockTransferShipInput, idempotencyKey: string) => Promise<void>;
  productNames?: ReadonlyMap<string, string>;
  transferId: string;
}>) {
  const rootErrorId = useId();
  const [submissionError, setSubmissionError] = useState<UiError>();
  const [outcomeUnknown, setOutcomeUnknown] = useState(false);
  const mutationManager = useRef(new CriticalMutationManager());
  const intentId = `ship-stock-transfer:${transferId}`;
  const form = useZanduForm<StockTransferShipFormValues>(stockTransferShipSchema, {
    defaultValues: {
      lines: lines.map((line) => ({
        lineId: line.id,
        shippedQuantity: line.requestedQuantity,
      })),
    },
  });
  useUnsavedChangesWarning(form.formState.isDirty);

  const submit = form.handleSubmit(async (values) => {
    if (
      !window.confirm(
        "Expédier ce transfert ? Les stocks du magasin source seront diminués immédiatement.",
      )
    ) {
      return;
    }
    form.clearErrors("root");
    setSubmissionError(undefined);
    const attempt = mutationManager.current.begin(intentId);
    try {
      await onShip(toStockTransferShipInput(values), attempt.key!);
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
      const mapped = mapShipmentError(error);
      setSubmissionError(mapped);
      form.setError("root", { message: mapped.message, type: "server" });
    }
  });

  return (
    <section aria-labelledby="ship-stock-transfer">
      <h3 id="ship-stock-transfer">Expédier le transfert</h3>
      <p>
        Confirmez chaque quantité réellement expédiée. Une valeur nulle est acceptée ; le serveur
        vérifie les quantités demandées et le stock disponible atomiquement.
      </p>
      <form aria-label="Expédier le transfert" noValidate onSubmit={submit}>
        {lines.map((line, index) => {
          const error = form.formState.errors.lines?.[index]?.shippedQuantity;
          const errorId = `${rootErrorId}-line-${index}`;
          return (
            <div key={line.id}>
              <input type="hidden" {...form.register(`lines.${index}.lineId`)} />
              <label>
                Quantité expédiée — {productNames.get(line.productId) ?? line.productId}
                <Input
                  {...form.register(`lines.${index}.shippedQuantity`)}
                  aria-describedby={error ? errorId : undefined}
                  aria-invalid={error ? true : undefined}
                  inputMode="decimal"
                />
              </label>
              <p>Quantité demandée : {line.requestedQuantity}</p>
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
            ? "Expédition en cours"
            : outcomeUnknown
              ? "Réessayer avec la même clé"
              : "Confirmer l’expédition"}
        </Button>
      </form>
    </section>
  );
}

function mapShipmentError(error: unknown): UiError {
  if (hasUnknownInventoryMutationOutcome(error)) {
    const mapped = errorMapper.map(
      error instanceof ApiRequestError ? error.apiError : { kind: "response", status: 500 },
    );
    return {
      ...(mapped.correlationId === undefined ? {} : { correlationId: mapped.correlationId }),
      message:
        "L’expédition a peut-être été enregistrée. Le statut et les stocks sont actualisés ; si le transfert reste en brouillon, réessayez explicitement avec la même clé.",
      retryable: true,
      title: "Résultat à vérifier",
    };
  }
  return errorMapper.map(
    error instanceof ApiRequestError ? error.apiError : { kind: "response", status: 500 },
    {
      STOCK_TRANSFER_CONFLICT: {
        message: "L’expédition concurrente est incomplète. Rechargez le transfert.",
        retryable: false,
        title: "Transfert en conflit",
      },
      STOCK_TRANSFER_NOT_EDITABLE: {
        message: "Seul un transfert en brouillon peut être expédié.",
        retryable: false,
        title: "Expédition impossible",
      },
      TRANSFER_SHIPPED_QUANTITY_EXCEEDS_REQUESTED: {
        message: "Une quantité expédiée dépasse la quantité demandée.",
        retryable: false,
        title: "Quantité invalide",
      },
      VALUATION_NOT_INITIALIZED: {
        message: "La valorisation d’un produit source doit être initialisée avant l’expédition.",
        retryable: false,
        title: "Valorisation non initialisée",
      },
    },
  );
}
