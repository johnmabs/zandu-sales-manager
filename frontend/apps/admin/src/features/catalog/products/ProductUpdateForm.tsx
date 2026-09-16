"use client";

import { ApiRequestError } from "@zandu/api-client";
import { ErrorMapper, type UiError } from "@zandu/error-contract";
import { useUnsavedChangesWarning, useZanduForm } from "@zandu/forms";
import { Button, Input } from "@zandu/ui";
import { useId, useState } from "react";

import { hasUnknownCatalogMutationOutcome } from "../mutationSafety";
import {
  productUpdateDefaults,
  toProductUpdateInput,
  updateProductSchema,
} from "../schemas/updateProductSchema";

import type { UpdateProductFormValues } from "../schemas/updateProductSchema";
import type { ProductResource, ProductUpdateInput } from "@zandu/api-client";

const errorMapper = new ErrorMapper();

export function ProductUpdateForm({
  onConflictReload,
  onUpdate,
  product,
}: Readonly<{
  onConflictReload: () => void;
  onUpdate: (input: ProductUpdateInput) => Promise<void>;
  product: ProductResource;
}>) {
  const nameErrorId = useId();
  const rootErrorId = useId();
  const [submissionError, setSubmissionError] = useState<UiError>();
  const [outcomeUnknown, setOutcomeUnknown] = useState(false);
  const form = useZanduForm<UpdateProductFormValues>(updateProductSchema, {
    defaultValues: productUpdateDefaults(product),
  });
  useUnsavedChangesWarning(form.formState.isDirty);
  const immutable = product.status !== "DRAFT";
  const submit = form.handleSubmit(async (values) => {
    if (outcomeUnknown) return;
    form.clearErrors("root");
    setSubmissionError(undefined);
    try {
      await onUpdate(toProductUpdateInput(values, product.status, product.version));
    } catch (error: unknown) {
      setSubmissionError(applyUpdateErrors(form, error));
      setOutcomeUnknown(hasUnknownCatalogMutationOutcome(error));
    }
  });
  const concurrency = form.formState.errors.root?.type === "concurrency";
  return (
    <form noValidate onSubmit={submit}>
      <dl>
        <dt>Statut</dt>
        <dd>{product.status}</dd>
        <dt>Version chargée</dt>
        <dd>{product.version}</dd>
      </dl>
      {immutable ? (
        <>
          <p>Code produit : {product.productCode}</p>
          <p>Unité de base : {product.baseUnitId}</p>
          <p>Le code et l’unité de base sont immuables après activation.</p>
        </>
      ) : (
        <>
          <label>
            Code
            <Input {...form.register("productCode")} />
          </label>
          <label>
            Identifiant de l’unité de base
            <Input {...form.register("baseUnitId")} />
          </label>
        </>
      )}
      <label>
        Nom
        <Input
          {...form.register("name")}
          aria-invalid={!!form.formState.errors.name || undefined}
          aria-describedby={form.formState.errors.name ? nameErrorId : undefined}
        />
      </label>
      <FieldError id={nameErrorId} message={form.formState.errors.name?.message} />
      <label>
        Description
        <Input {...form.register("description")} />
      </label>
      <label>
        Type
        <select {...form.register("type")}>
          <option value="PHYSICAL">Produit physique</option>
          <option value="SERVICE">Service</option>
        </select>
      </label>
      <label>
        <input type="checkbox" {...form.register("inventoryTracked")} /> Suivi de stock
      </label>
      <label>
        Identifiant de catégorie
        <Input {...form.register("categoryId")} />
      </label>
      <label>
        Identifiant de catégorie fiscale
        <Input {...form.register("taxCategoryId")} />
      </label>
      <FieldError
        id={rootErrorId}
        message={form.formState.errors.root?.message}
        correlationId={submissionError?.correlationId}
      />
      {concurrency ? (
        <Button type="button" onClick={onConflictReload}>
          Recharger les données
        </Button>
      ) : null}
      <Button disabled={form.formState.isSubmitting || outcomeUnknown} type="submit">
        {form.formState.isSubmitting
          ? "Enregistrement en cours"
          : outcomeUnknown
            ? "Résultat à vérifier"
            : "Enregistrer les modifications"}
      </Button>
    </form>
  );
}

function applyUpdateErrors(
  form: ReturnType<typeof useZanduForm<UpdateProductFormValues>>,
  error: unknown,
): UiError {
  if (hasUnknownCatalogMutationOutcome(error)) {
    const mapped = errorMapper.map(
      error instanceof ApiRequestError ? error.apiError : { kind: "response", status: 500 },
    );
    const result = {
      ...mapped,
      message:
        "La modification a peut-être été enregistrée. Rechargez le produit avant de recommencer.",
      retryable: false,
      title: "Résultat de modification à vérifier",
    };
    form.setError("root", { message: result.message, type: "unknown-outcome" });
    return result;
  }
  const mapped = errorMapper.map(
    error instanceof ApiRequestError ? error.apiError : { kind: "response", status: 500 },
    {
      CONFLICT: {
        message: "Ce code produit est déjà utilisé ou le produit a été modifié entre-temps.",
        retryable: false,
        title: "Modification en conflit",
      },
      DOMAIN_RULE_VIOLATION: {
        message: "La modification est interdite dans l’état actuel du produit.",
        retryable: false,
        title: "Modification impossible",
      },
    },
  );
  if (
    error instanceof ApiRequestError &&
    error.apiError.kind === "response" &&
    error.apiError.status === 409
  ) {
    form.setError("root", {
      message: "Le produit a changé entre-temps. Rechargez les données avant de réessayer.",
      type: "concurrency",
    });
    return mapped;
  }
  if (mapped.fieldErrors)
    for (const field of [
      "baseUnitId",
      "categoryId",
      "description",
      "inventoryTracked",
      "name",
      "productCode",
      "taxCategoryId",
      "type",
    ] as const) {
      const message = mapped.fieldErrors[field]?.[0];
      if (message) form.setError(field, { message, type: "server" });
    }
  form.setError("root", { message: mapped.message, type: "server" });
  return mapped;
}
function FieldError({
  correlationId,
  id,
  message,
}: Readonly<{ correlationId?: string | undefined; id: string; message?: string | undefined }>) {
  return message ? (
    <div id={id} role="alert">
      <p>{message}</p>
      {correlationId ? <p>Référence de diagnostic : {correlationId}</p> : null}
    </div>
  ) : null;
}
