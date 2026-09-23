"use client";

import { useUnsavedChangesWarning, useZanduForm } from "@zandu/forms";
import { Button, EmptyState, Input } from "@zandu/ui";
import { useId, useState } from "react";

import { hasUnknownInventoryMutationOutcome } from "../mutationSafety";

import { MutationFieldError, mapTransferMutationError } from "./CreateStockTransferForm";
import {
  stockTransferLineQuantitySchema,
  stockTransferLineSchema,
  toStockTransferLineCreateInput,
} from "./stockTransferSchemas";

import type {
  StockTransferLineFormValues,
  StockTransferLineQuantityFormValues,
} from "./stockTransferSchemas";
import type {
  ProductResource,
  StockTransferLineCreateInput,
  StockTransferLineResource,
  StockTransferLineUpdateInput,
} from "@zandu/api-client";
import type { UiError } from "@zandu/error-contract";

export function StockTransferDraftEditor({
  lines,
  onAdd,
  onRemove,
  onUpdate,
  products,
  version,
}: Readonly<{
  lines: readonly StockTransferLineResource[];
  onAdd: (input: StockTransferLineCreateInput) => Promise<void>;
  onRemove: (lineId: string) => Promise<void>;
  onUpdate: (lineId: string, input: StockTransferLineUpdateInput) => Promise<void>;
  products?: readonly ProductResource[];
  version: number;
}>) {
  const productNames = new Map(products?.map((product) => [product.id, product.name]));
  return (
    <section aria-labelledby="stock-transfer-draft-editor">
      <h3 id="stock-transfer-draft-editor">Modifier le brouillon</h3>
      <p>Les quantités demandées restent des décimaux exacts et strictement positifs.</p>
      {products === undefined ? (
        <p>La permission de consulter les produits est requise pour ajouter une ligne.</p>
      ) : (
        <AddStockTransferLineForm lines={lines} onAdd={onAdd} products={products} />
      )}
      {lines.length === 0 ? (
        <EmptyState
          title="Aucune ligne"
          description="Ajoutez au moins une ligne avant l’expédition du transfert."
        />
      ) : (
        <ul aria-label="Lignes modifiables du transfert">
          {lines.map((line) => (
            <li key={line.id}>
              <EditStockTransferLineForm
                line={line}
                onRemove={onRemove}
                onUpdate={onUpdate}
                productLabel={productNames.get(line.productId) ?? line.productId}
                version={version}
              />
            </li>
          ))}
        </ul>
      )}
    </section>
  );
}

function AddStockTransferLineForm({
  lines,
  onAdd,
  products,
}: Readonly<{
  lines: readonly StockTransferLineResource[];
  onAdd: (input: StockTransferLineCreateInput) => Promise<void>;
  products: readonly ProductResource[];
}>) {
  const productErrorId = useId();
  const quantityErrorId = useId();
  const rootErrorId = useId();
  const [submissionError, setSubmissionError] = useState<UiError>();
  const [outcomeUnknown, setOutcomeUnknown] = useState(false);
  const form = useZanduForm<StockTransferLineFormValues>(stockTransferLineSchema, {
    defaultValues: { productId: "", requestedQuantity: "" },
  });
  useUnsavedChangesWarning(form.formState.isDirty);
  const existingProductIds = new Set(lines.map((line) => line.productId));
  const availableProducts = products.filter((product) => !existingProductIds.has(product.id));
  const submit = form.handleSubmit(async (values) => {
    if (outcomeUnknown) return;
    form.clearErrors("root");
    setSubmissionError(undefined);
    try {
      await onAdd(toStockTransferLineCreateInput(values));
      form.reset();
    } catch (error: unknown) {
      const mapped = mapTransferMutationError(error, "création de la ligne");
      setSubmissionError(mapped);
      setOutcomeUnknown(hasUnknownInventoryMutationOutcome(error));
      for (const field of ["productId", "requestedQuantity"] as const) {
        const message = mapped.fieldErrors?.[field]?.[0];
        if (message !== undefined) form.setError(field, { message, type: "server" });
      }
      form.setError("root", { message: mapped.message, type: "server" });
    }
  });
  return (
    <form aria-label="Ajouter une ligne au transfert" noValidate onSubmit={submit}>
      <label>
        Produit
        <select
          {...form.register("productId")}
          aria-describedby={form.formState.errors.productId ? productErrorId : undefined}
          aria-invalid={form.formState.errors.productId ? true : undefined}
        >
          <option value="">Sélectionner</option>
          {availableProducts.map((product) => (
            <option key={product.id} value={product.id}>
              {product.name} ({product.productCode})
            </option>
          ))}
        </select>
      </label>
      <MutationFieldError id={productErrorId} message={form.formState.errors.productId?.message} />
      <label>
        Quantité demandée
        <Input
          {...form.register("requestedQuantity")}
          aria-describedby={form.formState.errors.requestedQuantity ? quantityErrorId : undefined}
          aria-invalid={form.formState.errors.requestedQuantity ? true : undefined}
          inputMode="decimal"
        />
      </label>
      <MutationFieldError
        id={quantityErrorId}
        message={form.formState.errors.requestedQuantity?.message}
      />
      <MutationFieldError
        correlationId={submissionError?.correlationId}
        id={rootErrorId}
        message={form.formState.errors.root?.message}
      />
      <Button
        disabled={form.formState.isSubmitting || outcomeUnknown || availableProducts.length === 0}
        type="submit"
      >
        {form.formState.isSubmitting
          ? "Ajout en cours"
          : outcomeUnknown
            ? "Résultat à vérifier"
            : "Ajouter la ligne"}
      </Button>
    </form>
  );
}

function EditStockTransferLineForm({
  line,
  onRemove,
  onUpdate,
  productLabel,
  version,
}: Readonly<{
  line: StockTransferLineResource;
  onRemove: (lineId: string) => Promise<void>;
  onUpdate: (lineId: string, input: StockTransferLineUpdateInput) => Promise<void>;
  productLabel: string;
  version: number;
}>) {
  const quantityErrorId = useId();
  const rootErrorId = useId();
  const [submissionError, setSubmissionError] = useState<UiError>();
  const [removing, setRemoving] = useState(false);
  const [outcomeUnknown, setOutcomeUnknown] = useState(false);
  const form = useZanduForm<StockTransferLineQuantityFormValues>(stockTransferLineQuantitySchema, {
    defaultValues: { requestedQuantity: line.requestedQuantity },
  });
  useUnsavedChangesWarning(form.formState.isDirty);
  const submit = form.handleSubmit(async (values) => {
    if (outcomeUnknown) return;
    form.clearErrors("root");
    setSubmissionError(undefined);
    try {
      await onUpdate(line.id, {
        expectedVersion: version,
        requestedQuantity: values.requestedQuantity.trim(),
      });
      form.reset({ requestedQuantity: values.requestedQuantity.trim() });
    } catch (error: unknown) {
      const mapped = mapTransferMutationError(error, "modification de la ligne");
      setSubmissionError(mapped);
      setOutcomeUnknown(hasUnknownInventoryMutationOutcome(error));
      const fieldMessage = mapped.fieldErrors?.requestedQuantity?.[0];
      if (fieldMessage !== undefined) {
        form.setError("requestedQuantity", { message: fieldMessage, type: "server" });
      }
      form.setError("root", { message: mapped.message, type: "server" });
    }
  });
  const remove = async () => {
    if (outcomeUnknown) return;
    if (!window.confirm(`Retirer ${productLabel} du transfert ?`)) return;
    form.clearErrors("root");
    setSubmissionError(undefined);
    setRemoving(true);
    try {
      await onRemove(line.id);
    } catch (error: unknown) {
      const mapped = mapTransferMutationError(error, "suppression de la ligne");
      setSubmissionError(mapped);
      setOutcomeUnknown(hasUnknownInventoryMutationOutcome(error));
      form.setError("root", { message: mapped.message, type: "server" });
    } finally {
      setRemoving(false);
    }
  };
  return (
    <form aria-label={`Modifier la ligne ${productLabel}`} noValidate onSubmit={submit}>
      <p>{productLabel}</p>
      <label>
        Quantité demandée
        <Input
          {...form.register("requestedQuantity")}
          aria-describedby={form.formState.errors.requestedQuantity ? quantityErrorId : undefined}
          aria-invalid={form.formState.errors.requestedQuantity ? true : undefined}
          inputMode="decimal"
        />
      </label>
      <MutationFieldError
        id={quantityErrorId}
        message={form.formState.errors.requestedQuantity?.message}
      />
      <MutationFieldError
        correlationId={submissionError?.correlationId}
        id={rootErrorId}
        message={form.formState.errors.root?.message}
      />
      <Button disabled={form.formState.isSubmitting || removing || outcomeUnknown} type="submit">
        {form.formState.isSubmitting
          ? "Enregistrement"
          : outcomeUnknown
            ? "Résultat à vérifier"
            : "Enregistrer"}
      </Button>
      <Button
        disabled={form.formState.isSubmitting || removing || outcomeUnknown}
        onClick={() => void remove()}
        type="button"
      >
        {removing ? "Suppression" : "Retirer"}
      </Button>
    </form>
  );
}
