"use client";

import { useUnsavedChangesWarning, useZanduForm } from "@zandu/forms";
import { Button, Checkbox, Input } from "@zandu/ui";
import { useId } from "react";

import { inviteMemberSchema, toInvitationCreateInput } from "../schemas/inviteMemberSchema";

import type { RoleResource } from "@zandu/api-client";

type InviteMemberFormProperties = Readonly<{
  onInvite: (input: ReturnType<typeof toInvitationCreateInput>) => Promise<void>;
  roles: readonly RoleResource[];
}>;

export function InviteMemberForm({ onInvite, roles }: InviteMemberFormProperties) {
  const emailErrorId = useId();
  const roleErrorId = useId();
  const form = useZanduForm(inviteMemberSchema, {
    defaultValues: { email: "", expiresAt: "", roleCodes: [] },
  });
  useUnsavedChangesWarning(form.formState.isDirty);

  return (
    <form
      className="zandu-invite-member-form"
      noValidate
      onSubmit={form.handleSubmit(async (values) => onInvite(toInvitationCreateInput(values)))}
    >
      <label>
        Email
        <Input
          aria-invalid={form.formState.errors.email === undefined ? undefined : true}
          {...(form.formState.errors.email === undefined
            ? {}
            : { "aria-describedby": emailErrorId })}
          {...form.register("email")}
          type="email"
        />
      </label>
      <FieldError id={emailErrorId} message={form.formState.errors.email?.message} />
      <fieldset
        {...(form.formState.errors.roleCodes === undefined
          ? {}
          : { "aria-describedby": roleErrorId, "aria-invalid": true })}
      >
        <legend>Rôles</legend>
        {roles.map((role) => (
          <Checkbox
            key={role.id}
            label={`${role.name} (${role.code})`}
            value={role.code}
            {...form.register("roleCodes")}
          />
        ))}
      </fieldset>
      <FieldError id={roleErrorId} message={form.formState.errors.roleCodes?.message} />
      <label>
        Expiration (facultative)
        <Input {...form.register("expiresAt")} type="datetime-local" />
      </label>
      <p>Chaque rôle sélectionné s’applique à toute l’organisation.</p>
      <Button disabled={form.formState.isSubmitting} type="submit">
        {form.formState.isSubmitting ? "Invitation en cours" : "Envoyer l’invitation"}
      </Button>
    </form>
  );
}

function FieldError({ id, message }: Readonly<{ id: string; message: string | undefined }>) {
  return message === undefined ? null : (
    <p id={id} role="alert">
      {message}
    </p>
  );
}
