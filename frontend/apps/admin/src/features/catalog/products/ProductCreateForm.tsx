"use client";

import { ApiRequestError } from "@zandu/api-client";
import { ErrorMapper, type UiError } from "@zandu/error-contract";
import { useUnsavedChangesWarning, useZanduForm } from "@zandu/forms";
import { Button, Input } from "@zandu/ui";
import { useId, useState } from "react";

import { hasUnknownCatalogMutationOutcome } from "../mutationSafety";
import { createProductSchema, toProductCreateInput } from "../schemas/createProductSchema";

import type { CreateProductFormValues } from "../schemas/createProductSchema";
import type { ProductCreateInput } from "@zandu/api-client";

const errorMapper = new ErrorMapper();

export function ProductCreateForm({
  onCreate,
}: Readonly<{ onCreate: (input: ProductCreateInput) => Promise<void> }>) {
  const codeErrorId = useId();
  const nameErrorId = useId();
  const unitErrorId = useId();
  const rootErrorId = useId();
  const [submissionError, setSubmissionError] = useState<UiError>();
  const [outcomeUnknown, setOutcomeUnknown] = useState(false);
  const form = useZanduForm<CreateProductFormValues>(createProductSchema, {
    defaultValues: {
      baseUnitId: "",
      categoryId: "",
      description: "",
      inventoryTracked: true,
      name: "",
      productCode: "",
      taxCategoryId: "",
      type: "PHYSICAL",
    },
  });
  useUnsavedChangesWarning(form.formState.isDirty);
  const submit = form.handleSubmit(async (values) => {
    if (outcomeUnknown) return;
    form.clearErrors("root");
    setSubmissionError(undefined);
    try {
      await onCreate(toProductCreateInput(values));
    } catch (error: unknown) {
      const mapped = applyProductCreateErrors(form, error);
      setSubmissionError(mapped);
      setOutcomeUnknown(hasUnknownCatalogMutationOutcome(error));
    }
  });
  return (
    <form noValidate onSubmit={submit}>
      <label>
        Code
        <Input
          {...form.register("productCode")}
          aria-invalid={!!form.formState.errors.productCode || undefined}
          aria-describedby={form.formState.errors.productCode ? codeErrorId : undefined}
        />
      </label>
      <FieldError id={codeErrorId} message={form.formState.errors.productCode?.message} />
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
        Identifiant de l’unité de base
        <Input
          {...form.register("baseUnitId")}
          aria-invalid={!!form.formState.errors.baseUnitId || undefined}
          aria-describedby={form.formState.errors.baseUnitId ? unitErrorId : undefined}
        />
      </label>
      <FieldError id={unitErrorId} message={form.formState.errors.baseUnitId?.message} />
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
      <Button disabled={form.formState.isSubmitting || outcomeUnknown} type="submit">
        {form.formState.isSubmitting
          ? "Création en cours"
          : outcomeUnknown
            ? "Résultat à vérifier"
            : "Créer le produit"}
      </Button>
    </form>
  );
}

function applyProductCreateErrors(
  form: ReturnType<typeof useZanduForm<CreateProductFormValues>>,
  error: unknown,
): UiError {
  if (hasUnknownCatalogMutationOutcome(error)) {
    const mapped = errorMapper.map(
      error instanceof ApiRequestError ? error.apiError : { kind: "response", status: 500 },
    );
    const result = {
      ...mapped,
      message:
        "La création a peut-être été enregistrée. Vérifiez la liste des produits avant de recommencer.",
      retryable: false,
      title: "Résultat de création à vérifier",
    };
    form.setError("root", { message: result.message, type: "unknown-outcome" });
    return result;
  }
  const mapped = errorMapper.map(
    error instanceof ApiRequestError ? error.apiError : { kind: "response", status: 500 },
    {
      CONFLICT: {
        message: "Ce code produit est déjà utilisé dans l’organisation.",
        retryable: false,
        title: "Code déjà utilisé",
      },
      DOMAIN_RULE_VIOLATION: {
        message: "Les propriétés du produit ne respectent pas les règles du catalogue.",
        retryable: false,
        title: "Produit invalide",
      },
    },
  );
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
}: Readonly<{
  correlationId?: string | undefined;
  id: string;
  message?: string | undefined;
}>) {
  return message ? (
    <div id={id} role="alert">
      <p>{message}</p>
      {correlationId ? <p>Référence de diagnostic : {correlationId}</p> : null}
    </div>
  ) : null;
}
