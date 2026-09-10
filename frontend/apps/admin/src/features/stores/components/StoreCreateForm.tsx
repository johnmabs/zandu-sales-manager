"use client";

import { ApiRequestError, type StoreCreateInput } from "@zandu/api-client";
import { ErrorMapper } from "@zandu/error-contract";
import { useUnsavedChangesWarning, useZanduForm } from "@zandu/forms";
import { Button, Input } from "@zandu/ui";

import { createStoreSchema, toCreateStoreInput } from "../schemas/createStoreSchema";

import type { CreateStoreFormValues } from "../schemas/createStoreSchema";

type StoreCreateFormProperties = Readonly<{
  defaults: Omit<CreateStoreFormValues, "address" | "code" | "name">;
  onCreate: (input: StoreCreateInput) => Promise<void>;
}>;

const errorMapper = new ErrorMapper();

export function StoreCreateForm({ defaults, onCreate }: StoreCreateFormProperties) {
  const form = useZanduForm(createStoreSchema, {
    defaultValues: { address: "", code: "", name: "", ...defaults },
  });
  useUnsavedChangesWarning(form.formState.isDirty);

  const submit = form.handleSubmit(async (values) => {
    form.clearErrors("root");
    try {
      await onCreate(toCreateStoreInput(values));
    } catch (error: unknown) {
      applyCreateStoreErrors(form, error);
    }
  });

  return (
    <form noValidate onSubmit={submit}>
      <label>
        Code
        <Input
          aria-invalid={form.formState.errors.code === undefined ? undefined : true}
          {...form.register("code")}
        />
      </label>
      <FieldError message={form.formState.errors.code?.message} />
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
        <Input {...form.register("address")} />
      </label>
      <FieldError message={form.formState.errors.address?.message} />
      <label>
        Fuseau horaire
        <Input {...form.register("timeZone")} />
      </label>
      <FieldError message={form.formState.errors.timeZone?.message} />
      <label>
        Devise
        <Input {...form.register("currency")} />
      </label>
      <FieldError message={form.formState.errors.currency?.message} />
      <label>
        Langue
        <Input {...form.register("locale")} />
      </label>
      <FieldError message={form.formState.errors.locale?.message} />
      <FieldError message={form.formState.errors.root?.message} />
      <Button disabled={form.formState.isSubmitting} type="submit">
        {form.formState.isSubmitting ? "Création en cours" : "Créer le magasin"}
      </Button>
    </form>
  );
}

function applyCreateStoreErrors(
  form: ReturnType<typeof useZanduForm<CreateStoreFormValues>>,
  error: unknown,
): void {
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

    return;
  }

  form.setError("root", {
    message: "Une erreur inattendue est survenue. Réessayez ultérieurement.",
    type: "server",
  });
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

function FieldError({ message }: Readonly<{ message: string | undefined }>) {
  return message === undefined ? null : <p role="alert">{message}</p>;
}
