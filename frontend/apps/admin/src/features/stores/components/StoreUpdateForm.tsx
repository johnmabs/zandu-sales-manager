"use client";

import { ApiRequestError, type StoreResource, type StoreUpdateInput } from "@zandu/api-client";
import { ErrorMapper } from "@zandu/error-contract";
import { useUnsavedChangesWarning, useZanduForm } from "@zandu/forms";
import { Button, Input } from "@zandu/ui";

import { toUpdateStoreInput, updateStoreSchema } from "../schemas/updateStoreSchema";

import type { UpdateStoreFormValues } from "../schemas/updateStoreSchema";

type StoreUpdateFormProperties = Readonly<{
  onConflictReload: () => void;
  onUpdate: (input: StoreUpdateInput) => Promise<void>;
  store: StoreResource;
}>;

const errorMapper = new ErrorMapper();

export function StoreUpdateForm({ onConflictReload, onUpdate, store }: StoreUpdateFormProperties) {
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
    form.clearErrors("root");
    try {
      await onUpdate(toUpdateStoreInput(values));
    } catch (error: unknown) {
      applyUpdateStoreErrors(form, error);
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
      <FieldError message={form.formState.errors.root?.message} />
      {isConcurrencyConflict ? (
        <Button onClick={onConflictReload} type="button">
          Recharger les données
        </Button>
      ) : null}
      <Button disabled={form.formState.isSubmitting} type="submit">
        {form.formState.isSubmitting ? "Enregistrement en cours" : "Enregistrer les modifications"}
      </Button>
    </form>
  );
}

function applyUpdateStoreErrors(
  form: ReturnType<typeof useZanduForm<UpdateStoreFormValues>>,
  error: unknown,
): void {
  if (error instanceof ApiRequestError) {
    if (error.apiError.kind === "response" && error.apiError.status === 409) {
      form.setError("root", {
        message: "Ce magasin a été modifié entre-temps. Rechargez les données avant de réessayer.",
        type: "concurrency",
      });
      return;
    }

    const mapped = errorMapper.map(error.apiError, {
      DOMAIN_RULE_VIOLATION: {
        message: "La modification est indisponible dans l’état actuel du magasin.",
        retryable: false,
        title: "Modification impossible",
      },
    });
    if (mapped.fieldErrors !== undefined) {
      applyStoreUpdateFieldErrors(form, mapped.fieldErrors);
    }
    form.setError("root", { message: mapped.message, type: "server" });
    return;
  }

  form.setError("root", {
    message: "Une erreur inattendue est survenue. Réessayez ultérieurement.",
    type: "server",
  });
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

function FieldError({ message }: Readonly<{ message: string | undefined }>) {
  return message === undefined ? null : <p role="alert">{message}</p>;
}
