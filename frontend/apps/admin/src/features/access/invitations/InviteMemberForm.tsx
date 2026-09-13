"use client";

import { useUnsavedChangesWarning, useZanduForm } from "@zandu/forms";
import { Button, Input } from "@zandu/ui";
import { useId } from "react";

import { inviteMemberSchema, toInvitationCreateInput } from "../schemas/inviteMemberSchema";

type InviteMemberFormProperties = Readonly<{
  onInvite: (input: ReturnType<typeof toInvitationCreateInput>) => Promise<void>;
}>;

export function InviteMemberForm({ onInvite }: InviteMemberFormProperties) {
  const emailErrorId = useId();
  const roleErrorId = useId();
  const form = useZanduForm(inviteMemberSchema, {
    defaultValues: { email: "", expiresAt: "", roleCode: "" },
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
      <label>
        Code du rôle
        <Input
          aria-invalid={form.formState.errors.roleCode === undefined ? undefined : true}
          {...(form.formState.errors.roleCode === undefined
            ? {}
            : { "aria-describedby": roleErrorId })}
          {...form.register("roleCode")}
        />
      </label>
      <FieldError id={roleErrorId} message={form.formState.errors.roleCode?.message} />
      <label>
        Expiration (facultative)
        <Input {...form.register("expiresAt")} type="datetime-local" />
      </label>
      <p>Cette première invitation attribue le rôle à toute l’organisation.</p>
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
