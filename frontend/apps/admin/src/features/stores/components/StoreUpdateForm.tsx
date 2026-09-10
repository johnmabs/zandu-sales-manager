"use client";

import { ApiRequestError, type StoreResource, type StoreUpdateInput } from "@zandu/api-client";
import { ErrorMapper, type UiError } from "@zandu/error-contract";
import { useUnsavedChangesWarning, useZanduForm } from "@zandu/forms";
import { Button, Input } from "@zandu/ui";
import { useState } from "react";

import { hasUnknownStoreMutationOutcome } from "../mutationSafety";
import { toUpdateStoreInput, updateStoreSchema } from "../schemas/updateStoreSchema";

import type { UpdateStoreFormValues } from "../schemas/updateStoreSchema";

type StoreUpdateFormProperties = Readonly<{
  onConflictReload: () => void;
  onUpdate: (input: StoreUpdateInput) => Promise<void>;
  store: StoreResource;
}>;

const errorMapper = new ErrorMapper();

export function StoreUpdateForm({ onConflictReload, onUpdate, store }: StoreUpdateFormProperties) {
  const [submissionError, setSubmissionError] = useState<UiError>();
  const [outcomeUnknown, setOutcomeUnknown] = useState(false);
  const form = useZanduForm(updateStoreSchema, {
    defaultValues: {
      address: store.address ?? "",
      locale: store.locale,
      name: store.name,
      timeZone: store.timeZone,
    },
  });
  useUnsavedChangesWarning(form.formState.isDirty);

  const submit = form.handleSubmit(async (values) => {
    if (outcomeUnknown) {
      return;
    }
    form.clearErrors("root");
    setSubmissionError(undefined);
    setOutcomeUnknown(false);
    try {
      await onUpdate(toUpdateStoreInput(values));
    } catch (error: unknown) {
      setSubmissionError(applyUpdateStoreErrors(form, error));
      setOutcomeUnknown(hasUnknownStoreMutationOutcome(error));
    }
  });
  const isConcurrencyConflict = form.formState.errors.root?.type === "concurrency";

  return (
    <form noValidate onSubmit={submit}>
      <p>Code : {store.code}</p>
      <p>Devise : {store.currency}</p>
      <label>
        Nom
        <Input
          aria-invalid={form.formState.errors.name === undefined ? undefined : true}
          {...form.register("name")}
        />
      </label>
      <FieldError message={form.formState.errors.name?.message} />
      <label>
        Adresse
        <Input
          aria-invalid={form.formState.errors.address === undefined ? undefined : true}
          {...form.register("address")}
        />
      </label>
      <FieldError message={form.formState.errors.address?.message} />
      <label>
        Fuseau horaire
        <Input
          aria-invalid={form.formState.errors.timeZone === undefined ? undefined : true}
          {...form.register("timeZone")}
        />
      </label>
      <FieldError message={form.formState.errors.timeZone?.message} />
      <label>
        Langue
        <Input
          aria-invalid={form.formState.errors.locale === undefined ? undefined : true}
          {...form.register("locale")}
        />
      </label>
      <FieldError message={form.formState.errors.locale?.message} />
      <FieldError
        {...(submissionError?.correlationId === undefined
          ? {}
          : { correlationId: submissionError.correlationId })}
        message={form.formState.errors.root?.message}
      />
      {isConcurrencyConflict ? (
        <Button onClick={onConflictReload} type="button">
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

function applyUpdateStoreErrors(
  form: ReturnType<typeof useZanduForm<UpdateStoreFormValues>>,
  error: unknown,
): UiError {
  if (hasUnknownStoreMutationOutcome(error)) {
    const mapped = errorMapper.map(
      error instanceof ApiRequestError ? error.apiError : { kind: "response", status: 500 },
    );
    const outcomeError: UiError = {
      ...(mapped.correlationId === undefined ? {} : { correlationId: mapped.correlationId }),
      message:
        "La modification a peut-être été enregistrée. Rechargez les données avant de recommencer.",
      retryable: false,
      title: "Résultat de modification à vérifier",
    };
    form.setError("root", { message: outcomeError.message, type: "unknown-outcome" });
    return outcomeError;
  }
  if (error instanceof ApiRequestError) {
    const mapped = errorMapper.map(error.apiError, {
      DOMAIN_RULE_VIOLATION: {
        message: "La modification est indisponible dans l’état actuel du magasin.",
        retryable: false,
        title: "Modification impossible",
      },
    });
    if (error.apiError.kind === "response" && error.apiError.status === 409) {
      form.setError("root", {
        message: "Ce magasin a été modifié entre-temps. Rechargez les données avant de réessayer.",
        type: "concurrency",
      });
      return mapped;
    }

    if (mapped.fieldErrors !== undefined) {
      applyStoreUpdateFieldErrors(form, mapped.fieldErrors);
    }
    form.setError("root", { message: mapped.message, type: "server" });
    return mapped;
  }

  const mapped = errorMapper.map({ kind: "response", status: 500 });
  form.setError("root", {
    message: mapped.message,
    type: "server",
  });
  return mapped;
}

function applyStoreUpdateFieldErrors(
  form: ReturnType<typeof useZanduForm<UpdateStoreFormValues>>,
  fieldErrors: Readonly<Record<string, readonly string[]>>,
): void {
  for (const field of ["address", "locale", "name", "timeZone"] as const) {
    const message = fieldErrors[field]?.[0];
    if (message !== undefined) {
      form.setError(field, { message, type: "server" });
    }
  }
}

function FieldError({
  correlationId,
  message,
}: Readonly<{ correlationId?: string; message: string | undefined }>) {
  return message === undefined ? null : (
    <div role="alert">
      <p>{message}</p>
      {correlationId === undefined ? null : <p>Référence de diagnostic : {correlationId}</p>}
    </div>
  );
}
