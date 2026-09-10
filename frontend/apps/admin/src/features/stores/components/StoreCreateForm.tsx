"use client";

import { ApiRequestError, type StoreCreateInput } from "@zandu/api-client";
import { ErrorMapper, type UiError } from "@zandu/error-contract";
import { useUnsavedChangesWarning, useZanduForm } from "@zandu/forms";
import { Button, Input } from "@zandu/ui";
import { useId, useState } from "react";

import { hasUnknownStoreMutationOutcome } from "../mutationSafety";
import { createStoreSchema, toCreateStoreInput } from "../schemas/createStoreSchema";

import type { CreateStoreFormValues } from "../schemas/createStoreSchema";

type StoreCreateFormProperties = Readonly<{
  defaults: Omit<CreateStoreFormValues, "address" | "code" | "name">;
  onCreate: (input: StoreCreateInput) => Promise<void>;
}>;

const errorMapper = new ErrorMapper();

export function StoreCreateForm({ defaults, onCreate }: StoreCreateFormProperties) {
  const codeErrorId = useId();
  const nameErrorId = useId();
  const addressErrorId = useId();
  const timeZoneErrorId = useId();
  const currencyErrorId = useId();
  const localeErrorId = useId();
  const rootErrorId = useId();
  const [submissionError, setSubmissionError] = useState<UiError>();
  const [outcomeUnknown, setOutcomeUnknown] = useState(false);
  const form = useZanduForm(createStoreSchema, {
    defaultValues: { address: "", code: "", name: "", ...defaults },
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
      await onCreate(toCreateStoreInput(values));
    } catch (error: unknown) {
      setSubmissionError(applyCreateStoreErrors(form, error));
      setOutcomeUnknown(hasUnknownStoreMutationOutcome(error));
    }
  });

  return (
    <form className="zandu-store-form" noValidate onSubmit={submit}>
      <label>
        Code
        <Input
          {...describedBy(form.formState.errors.code?.message, codeErrorId)}
          aria-invalid={form.formState.errors.code === undefined ? undefined : true}
          {...form.register("code")}
        />
      </label>
      <FieldError id={codeErrorId} message={form.formState.errors.code?.message} />
      <label>
        Nom
        <Input
          {...describedBy(form.formState.errors.name?.message, nameErrorId)}
          aria-invalid={form.formState.errors.name === undefined ? undefined : true}
          {...form.register("name")}
        />
      </label>
      <FieldError id={nameErrorId} message={form.formState.errors.name?.message} />
      <label>
        Adresse
        <Input
          {...describedBy(form.formState.errors.address?.message, addressErrorId)}
          {...form.register("address")}
        />
      </label>
      <FieldError id={addressErrorId} message={form.formState.errors.address?.message} />
      <label>
        Fuseau horaire
        <Input
          {...describedBy(form.formState.errors.timeZone?.message, timeZoneErrorId)}
          {...form.register("timeZone")}
        />
      </label>
      <FieldError id={timeZoneErrorId} message={form.formState.errors.timeZone?.message} />
      <label>
        Devise
        <Input
          {...describedBy(form.formState.errors.currency?.message, currencyErrorId)}
          {...form.register("currency")}
        />
      </label>
      <FieldError id={currencyErrorId} message={form.formState.errors.currency?.message} />
      <label>
        Langue
        <Input
          {...describedBy(form.formState.errors.locale?.message, localeErrorId)}
          {...form.register("locale")}
        />
      </label>
      <FieldError id={localeErrorId} message={form.formState.errors.locale?.message} />
      <FieldError
        {...(submissionError?.correlationId === undefined
          ? {}
          : { correlationId: submissionError.correlationId })}
        id={rootErrorId}
        message={form.formState.errors.root?.message}
      />
      <Button disabled={form.formState.isSubmitting || outcomeUnknown} type="submit">
        {form.formState.isSubmitting
          ? "Création en cours"
          : outcomeUnknown
            ? "Résultat à vérifier"
            : "Créer le magasin"}
      </Button>
    </form>
  );
}

function applyCreateStoreErrors(
  form: ReturnType<typeof useZanduForm<CreateStoreFormValues>>,
  error: unknown,
): UiError {
  if (hasUnknownStoreMutationOutcome(error)) {
    const mapped = errorMapper.map(
      error instanceof ApiRequestError ? error.apiError : { kind: "response", status: 500 },
    );
    const outcomeError: UiError = {
      ...(mapped.correlationId === undefined ? {} : { correlationId: mapped.correlationId }),
      message:
        "La création a peut-être été enregistrée. Vérifiez la liste des magasins avant de recommencer.",
      retryable: false,
      title: "Résultat de création à vérifier",
    };
    form.setError("root", { message: outcomeError.message, type: "unknown-outcome" });
    return outcomeError;
  }
  if (error instanceof ApiRequestError) {
    const mapped = errorMapper.map(error.apiError, {
      CONFLICT: {
        message: "Ce code de magasin est déjà utilisé dans l’organisation.",
        retryable: false,
        title: "Code déjà utilisé",
      },
      DOMAIN_RULE_VIOLATION: {
        message: "La création est indisponible dans l’état actuel de l’organisation.",
        retryable: false,
        title: "Création impossible",
      },
    });
    if (mapped.fieldErrors !== undefined) {
      applyStoreCreateFieldErrors(form, mapped.fieldErrors);
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

function applyStoreCreateFieldErrors(
  form: ReturnType<typeof useZanduForm<CreateStoreFormValues>>,
  fieldErrors: Readonly<Record<string, readonly string[]>>,
): void {
  for (const field of ["address", "code", "currency", "locale", "name", "timeZone"] as const) {
    const message = fieldErrors[field]?.[0];
    if (message !== undefined) {
      form.setError(field, { message, type: "server" });
    }
  }
}

function FieldError({
  correlationId,
  id,
  message,
}: Readonly<{ correlationId?: string; id: string; message: string | undefined }>) {
  return message === undefined ? null : (
    <div id={id} role="alert">
      <p>{message}</p>
      {correlationId === undefined ? null : <p>Référence de diagnostic : {correlationId}</p>}
    </div>
  );
}

function describedBy(
  message: string | undefined,
  errorId: string,
): Readonly<{ "aria-describedby"?: string }> {
  return message === undefined ? {} : { "aria-describedby": errorId };
}
