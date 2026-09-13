"use client";

import { useZanduForm } from "@zandu/forms";
import { Button, Dialog, Input, Select } from "@zandu/ui";
import { useId } from "react";

import { assignRoleSchema, toRoleAssignmentInput } from "../schemas/assignRoleSchema";
import { StoreScopeSelector } from "../scopes/StoreScopeSelector";

import type { RoleAssignmentCreateInput, RoleResource, StoreResource } from "@zandu/api-client";

export function AssignRoleDialog({
  isAssigning,
  onClose,
  onConfirm,
  open,
  roles,
  stores,
}: Readonly<{
  isAssigning: boolean;
  onClose: () => void;
  onConfirm: (input: RoleAssignmentCreateInput) => Promise<void>;
  open: boolean;
  roles: readonly RoleResource[];
  stores: readonly StoreResource[];
}>) {
  const descriptionId = useId();
  const form = useZanduForm(assignRoleSchema, {
    defaultValues: { expiresAt: "", roleId: "", scopeType: "ORGANIZATION", storeIds: [] },
  });
  const scopeType = form.watch("scopeType");
  const storeIds = form.watch("storeIds");

  return (
    <Dialog descriptionId={descriptionId} onClose={onClose} open={open} title="Attribuer un rôle">
      <p id={descriptionId}>Choisissez le rôle, sa portée et son expiration éventuelle.</p>
      <form onSubmit={form.handleSubmit((value) => onConfirm(toRoleAssignmentInput(value)))}>
        <label>
          Rôle
          <Select {...form.register("roleId")}>
            <option value="">Sélectionner</option>
            {roles
              .filter((role) => role.status === "ACTIVE")
              .map((role) => (
                <option key={role.id} value={role.id}>
                  {role.name} ({role.code})
                </option>
              ))}
          </Select>
        </label>
        {form.formState.errors.roleId === undefined ? null : (
          <p role="alert">{form.formState.errors.roleId.message}</p>
        )}
        <StoreScopeSelector
          isLoading={false}
          onScopeTypeChange={(next) => {
            form.setValue("scopeType", next, { shouldValidate: true });
            if (next === "ORGANIZATION") form.setValue("storeIds", [], { shouldValidate: true });
          }}
          onSelectedStoreIdsChange={(ids) =>
            form.setValue("storeIds", [...ids], { shouldValidate: true })
          }
          scopeType={scopeType}
          selectedStoreIds={storeIds}
          stores={stores}
        />
        {form.formState.errors.storeIds === undefined ? null : (
          <p role="alert">{form.formState.errors.storeIds.message}</p>
        )}
        <label>
          Expiration
          <Input {...form.register("expiresAt")} type="datetime-local" />
        </label>
        <Button disabled={isAssigning} onClick={onClose} type="button" variant="secondary">
          Annuler
        </Button>
        <Button disabled={isAssigning} type="submit">
          {isAssigning ? "Attribution en cours" : "Attribuer le rôle"}
        </Button>
      </form>
    </Dialog>
  );
}
